<?php
/*
 * @copyright 2026 HtmlViewer
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the HtmlViewer App
 * created by Marius David Wieschollek.
 */

declare(strict_types=1);

namespace OCA\HtmlViewer\Service;

use OC\Security\CSP\ContentSecurityPolicyNonceManager;
use OCP\AppFramework\Services\IAppConfig;
use OCP\AppFramework\Services\IInitialState;

class ViewerStateService implements ViewerStateServiceInterface {

    public function __construct(
        protected ContentSecurityPolicyNonceManager $nonceManager,
        protected IInitialState                     $initialState,
        protected IAppConfig                        $config,
        protected SettingsServiceInterface          $settings,
    ) {
    }

    public function provideViewerState(?string $userId): void {
        $this->provideMaxFileSize();
        $this->provideUserSettings($userId);
        $this->provideJavaScriptSettings();
    }

    /**
     * Provide the file size limits in bytes.
     *
     * @return void
     */
    protected function provideMaxFileSize(): void {
        $limits = $this->settings->getFileSizeLimits();
        $this->initialState->provideInitialState('maxSize', $limits->maxSize);
        $this->initialState->provideInitialState('maxEditorSize', $limits->maxEditorSize);
        $this->initialState->provideInitialState('maxCodeEditorSize', $limits->maxCodeEditorSize);
    }

    /**
     * @param string|null $userId
     *
     * @return void
     */
    protected function provideUserSettings(?string $userId): void {
        $disableWarning = false;
        if($userId !== null) {
            $disableWarning = intval($this->config->getUserValue($userId, 'disableWarning', '0'));
        }
        $this->initialState->provideInitialState('disableWarning', $disableWarning === 1);
    }

    /**
     * @return void
     */
    protected function provideJavaScriptSettings(): void {
        $defaultCsp     = "default-src blob: data: ; style-src: 'unsafe-inline' blob: data:";
        $defaultSandbox = '';

        if($this->config->getAppValueBool('allowJs')) {
            $this->initialState->provideInitialState('allowJs', true);
            $this->initialState->provideInitialState('nonce', $this->nonceManager->getNonce());
            $defaultCsp     = "default-src 'unsafe-eval' 'unsafe-inline' 'wasm-unsafe-eval' blob: data:";
            $defaultSandbox = 'allow-scripts allow-presentation allow-modals allow-downloads';
        } else {
            $this->initialState->provideInitialState('allowJs', false);
        }

        $csp = $this->config->getAppValueString('csp', $defaultCsp);
        $this->initialState->provideInitialState('csp', $csp);

        $sandbox = $this->config->getAppValueString('sandbox', $defaultSandbox);
        if($this->config->getAppValueBool('newTabLinks')) {
            $sandboxTokens = preg_split('/\s+/', $sandbox, -1, PREG_SPLIT_NO_EMPTY);
            foreach(['allow-popups', 'allow-popups-to-escape-sandbox'] as $token) {
                if(!in_array($token, $sandboxTokens, true)) {
                    $sandboxTokens[] = $token;
                }
            }
            $sandbox = implode(' ', $sandboxTokens);
            $this->initialState->provideInitialState('newTabLinks', true);
        } else {
            $this->initialState->provideInitialState('newTabLinks', false);
        }

        $this->initialState->provideInitialState('sandbox', $sandbox);
    }
}