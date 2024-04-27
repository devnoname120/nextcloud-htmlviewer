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

        Util::addScript(Application::APP_ID, 'htmlviewer-main', 'viewer');
        Util::addTranslations(Application::APP_ID);
        if($this->appManager->isEnabledForUser('text')) {
            Util::addScript('text', null, Application::APP_ID);
        }

        $maxSize = intval($this->config->getAppValue('maxSize', 32)) * 1024 * 1024;
        $this->initialState->provideInitialState('maxSize', $maxSize);

        $disableWarning = false;
        if($this->userId !== null) {
            $disableWarning = intval($this->config->getUserValue($this->userId, 'disableWarning', 0));
        }
        $this->initialState->provideInitialState('disableWarning', $disableWarning === 1);

        if($this->config->getAppValue('allowJs') === '1') {
            $this->initialState->provideInitialState('allowJs', true);
            $this->initialState->provideInitialState('nonce', $this->nonceManager->getNonce());
        } else {
            $this->initialState->provideInitialState('allowJs', false);
        }
    }
}