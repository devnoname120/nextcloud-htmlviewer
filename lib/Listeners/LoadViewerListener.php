<?php
/*
 * @copyright 2024 HtmlViewer
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the HtmlViewer App
 * created by Marius David Wieschollek.
 */

namespace OCA\HtmlViewer\Listeners;

use OC\Security\CSP\ContentSecurityPolicyNonceManager;
use OCA\HtmlViewer\AppInfo\Application;
use OCA\Viewer\Event\LoadViewer;
use OCP\App\IAppManager;
use OCP\AppFramework\Services\IAppConfig;
use OCP\AppFramework\Services\IInitialState;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Util;

class LoadViewerListener implements IEventListener {

    public function __construct(
        protected                                   $userId,
        protected ContentSecurityPolicyNonceManager $nonceManager,
        protected IInitialState                     $initialState,
        protected IAppManager                       $appManager,
        protected IAppConfig                        $config,
    ) {
    }

    public function handle(Event $event): void {
        if(!$event instanceof LoadViewer) {
            return;
        }

        $this->addScripts();
        $this->provideMaxFileSize();
        $this->provideUserSettings();
        $this->provideJavaScriptSettings();
    }

    /**
     * @return void
     */
    protected function addScripts(): void {
        Util::addScript(Application::APP_ID, 'htmlviewer-main', 'viewer');
        Util::addTranslations(Application::APP_ID);
        if($this->appManager->isEnabledForUser('text')) {
            Util::addScript('text', null, Application::APP_ID);
        }
    }

    /**
     * @return void
     */
    protected function provideMaxFileSize(): void {
        $maxSize = intval($this->config->getAppValue('maxSize', 32)) * 1024 * 1024;
        $this->initialState->provideInitialState('maxSize', $maxSize);
    }

    /**
     * @return void
     */
    protected function provideUserSettings(): void {
        $disableWarning = false;
        if($this->userId !== null) {
            $disableWarning = intval($this->config->getUserValue($this->userId, 'disableWarning', 0));
        }
        $this->initialState->provideInitialState('disableWarning', $disableWarning === 1);
    }

    /**
     * @return void
     */
    protected function provideJavaScriptSettings(): void {
        $defaultCsp = "'default-src blob: data: ; style-src: \'unsafe-inline\' blob: data:";
        if($this->config->getAppValue('allowJs') === '1') {
            $this->initialState->provideInitialState('allowJs', true);
            $this->initialState->provideInitialState('nonce', $this->nonceManager->getNonce());
            $defaultCsp = "'default-src \'unsafe-eval\' \'unsafe-inline\' \'wasm-unsafe-eval\' blob: data:'";
        } else {
            $this->initialState->provideInitialState('allowJs', false);
        }

        $csp = $this->config->getAppValue('csp', $defaultCsp);
        $this->initialState->provideInitialState('csp', $csp);
    }
}