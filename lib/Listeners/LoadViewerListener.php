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

use OCA\HtmlViewer\Service\AssetServiceInterface;
use OCA\HtmlViewer\Service\ViewerStateServiceInterface;
use OCA\Viewer\Event\LoadViewer;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

class LoadViewerListener implements IEventListener {

    public function __construct(
        protected                             $userId,
        protected ViewerStateServiceInterface $viewerState,
        protected AssetServiceInterface       $assets,
    ) {
    }

    public function handle(Event $event): void {
        if(!$event instanceof LoadViewer) {
            return;
        }

        $this->assets->loadViewerAssets();
        $this->viewerState->provideViewerState($this->userId);
    }
}