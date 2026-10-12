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

namespace OCA\HtmlViewer\Listeners;

use OCA\HtmlViewer\DirectEditing\HtmlDirectEditor;
use OCA\HtmlViewer\Service\SettingsServiceInterface;
use OCP\DirectEditing\RegisterDirectEditorEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/**
 * @template-implements IEventListener<RegisterDirectEditorEvent>
 */
class RegisterDirectEditorListener implements IEventListener {

    public function __construct(
        protected HtmlDirectEditor         $editor,
        protected SettingsServiceInterface $settings,
    ) {
    }

    public function handle(Event $event): void {
        if(!$event instanceof RegisterDirectEditorEvent || !$this->settings->isDirectEditingEnabled()) {
            return;
        }

        $event->register($this->editor);
    }
}