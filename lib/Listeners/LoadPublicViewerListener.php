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
use OCP\App\IAppManager;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IAppConfig;
use OCP\AppFramework\Services\IInitialState;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Util;

class LoadPublicViewerListener implements IEventListener {

    public function __construct(
        protected ContentSecurityPolicyNonceManager $nonceManager,
        protected IInitialState                     $initialState,
        protected IAppManager                       $appManager,
        protected IAppConfig                        $config,
    ) {
    }

    public function handle(Event $event): void {
        if(!$event instanceof BeforeTemplateRenderedEvent) {
            return;
        }

        // Make sure we are on a public page rendering
        if($event->getResponse()->getRenderAs() !== TemplateResponse::RENDER_AS_PUBLIC) {
            return;
        }

        Util::addScript(Application::APP_ID, 'htmlviewer-public');
    }
}