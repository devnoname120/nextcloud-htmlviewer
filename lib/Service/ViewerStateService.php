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
use OCP\AppFramework\Services\IInitialState;

class ViewerStateService implements ViewerStateServiceInterface {

    public function __construct(
        protected ContentSecurityPolicyNonceManager $nonceManager,
        protected IInitialState                     $initialState,
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
        $disableWarning = $userId !== null && $this->settings->isScriptWarningDisabled($userId);
        $this->initialState->provideInitialState('disableWarning', $disableWarning);
    }

    /**
     * @return void
     */
    protected function provideJavaScriptSettings(): void {
        $settings = $this->settings->getAppSettings();
        $policy   = $this->settings->getIframePolicy();

        $this->initialState->provideInitialState('allowJs', $settings->allowJs);
        if($settings->allowJs) {
            $this->initialState->provideInitialState('nonce', $this->nonceManager->getNonce());
        }

        $this->initialState->provideInitialState('csp', $policy->csp);

        $sandbox = $policy->sandbox;
        if($settings->newTabLinks) {
            $sandboxTokens = preg_split('/\s+/', $sandbox, -1, PREG_SPLIT_NO_EMPTY);
            foreach(['allow-popups', 'allow-popups-to-escape-sandbox'] as $token) {
                if(!in_array($token, $sandboxTokens, true)) {
                    $sandboxTokens[] = $token;
                }
            }
            $sandbox = implode(' ', $sandboxTokens);
        }
        $this->initialState->provideInitialState('newTabLinks', $settings->newTabLinks);

        $this->initialState->provideInitialState('sandbox', $sandbox);
    }
}