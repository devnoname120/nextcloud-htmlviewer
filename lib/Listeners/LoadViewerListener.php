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

use OCA\HtmlViewer\AppInfo\Application;
use OCA\HtmlViewer\Service\ViewerStateServiceInterface;
use OCA\Viewer\Event\LoadViewer;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Util;

class LoadViewerListener implements IEventListener {

    public function __construct(
        protected                             $userId,
        protected ViewerStateServiceInterface $viewerState,
    ) {
    }

    public function handle(Event $event): void {
        if(!$event instanceof LoadViewer) {
            return;
        }

        $this->addScripts();
        $this->viewerState->provideViewerState($this->userId);
    }

    /**
     * @return void
     */
    protected function addScripts(): void {
        Util::addScript(Application::APP_ID, 'htmlviewer-main', 'viewer');
        Util::addTranslations(Application::APP_ID);
    }
}