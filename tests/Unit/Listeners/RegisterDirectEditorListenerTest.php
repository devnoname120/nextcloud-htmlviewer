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

namespace OCA\HtmlViewer\Tests\Unit\Listeners;

use OCA\HtmlViewer\DirectEditing\HtmlDirectEditor;
use OCA\HtmlViewer\Listeners\RegisterDirectEditorListener;
use OCA\HtmlViewer\Service\SettingsServiceInterface;
use OCP\DirectEditing\RegisterDirectEditorEvent;
use OCP\EventDispatcher\Event;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RegisterDirectEditorListener::class)]
class RegisterDirectEditorListenerTest extends TestCase {

    public function testRegistersEditorWhenEnabled(): void {
        $editor = $this->createStub(HtmlDirectEditor::class);
        $event = $this->createMock(RegisterDirectEditorEvent::class);
        $event->expects($this->once())->method('register')->with($this->identicalTo($editor));

        (new RegisterDirectEditorListener($editor, $this->createSettings(true)))->handle($event);
    }

    public function testDoesNotRegisterEditorWhenDisabled(): void {
        $event = $this->createMock(RegisterDirectEditorEvent::class);
        $event->expects($this->never())->method('register');

        (new RegisterDirectEditorListener($this->createStub(HtmlDirectEditor::class), $this->createSettings(false)))->handle($event);
    }

    public function testIgnoresOtherEvents(): void {
        $settings = $this->createMock(SettingsServiceInterface::class);
        $settings->expects($this->never())->method('isDirectEditingEnabled');

        (new RegisterDirectEditorListener($this->createStub(HtmlDirectEditor::class), $settings))->handle(new Event());
    }

    protected function createSettings(bool $enabled): SettingsServiceInterface {
        $settings = $this->createStub(SettingsServiceInterface::class);
        $settings->method('isDirectEditingEnabled')->willReturn($enabled);

        return $settings;
    }
}
