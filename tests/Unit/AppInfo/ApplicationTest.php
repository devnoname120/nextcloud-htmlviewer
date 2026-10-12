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

namespace OCA\HtmlViewer\Tests\Unit\AppInfo;

use OCA\HtmlViewer\AppInfo\Application;
use OCA\HtmlViewer\Listeners\CSPListener;
use OCA\HtmlViewer\Listeners\LoadViewerListener;
use OCA\HtmlViewer\Listeners\RegisterDirectEditorListener;
use OCA\HtmlViewer\Service\AssetService;
use OCA\HtmlViewer\Service\AssetServiceInterface;
use OCA\HtmlViewer\Service\DirectEditingService;
use OCA\HtmlViewer\Service\DirectEditingServiceInterface;
use OCA\HtmlViewer\Service\SettingsService;
use OCA\HtmlViewer\Service\SettingsServiceInterface;
use OCA\HtmlViewer\Service\ViewerStateService;
use OCA\HtmlViewer\Service\ViewerStateServiceInterface;
use OCA\Viewer\Event\LoadViewer;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\DirectEditing\RegisterDirectEditorEvent;
use OCP\Security\CSP\AddContentSecurityPolicyEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

#[CoversClass(Application::class)]
class ApplicationTest extends TestCase {

    public function testRegistersListenersAndServices(): void {
        $listeners = [];
        $aliases = [];

        $context = $this->createMock(IRegistrationContext::class);
        $context->expects($this->exactly(3))
                ->method('registerEventListener')
                ->willReturnCallback(function (string $event, string $listener, int $priority = 0) use (&$listeners) {
                    $listeners[] = [$event, $listener, $priority];
                });
        $context->expects($this->exactly(4))
                ->method('registerServiceAlias')
                ->willReturnCallback(function (string $alias, string $target) use (&$aliases) {
                    $aliases[ $alias ] = $target;
                });

        // The constructor of App needs a running server, the registration does not
        $application = (new ReflectionClass(Application::class))->newInstanceWithoutConstructor();
        $application->register($context);

        $this->assertSame(
            [
                [LoadViewer::class, LoadViewerListener::class, 0],
                [AddContentSecurityPolicyEvent::class, CSPListener::class, 0],
                [RegisterDirectEditorEvent::class, RegisterDirectEditorListener::class, Application::DIRECT_EDITOR_PRIORITY],
            ],
            $listeners
        );
        $this->assertSame(
            [
                SettingsServiceInterface::class      => SettingsService::class,
                ViewerStateServiceInterface::class   => ViewerStateService::class,
                DirectEditingServiceInterface::class => DirectEditingService::class,
                AssetServiceInterface::class         => AssetService::class,
            ],
            $aliases
        );
    }

    public function testServiceAliasesPointToImplementations(): void {
        $this->assertTrue(is_subclass_of(SettingsService::class, SettingsServiceInterface::class));
        $this->assertTrue(is_subclass_of(ViewerStateService::class, ViewerStateServiceInterface::class));
        $this->assertTrue(is_subclass_of(DirectEditingService::class, DirectEditingServiceInterface::class));
        $this->assertTrue(is_subclass_of(AssetService::class, AssetServiceInterface::class));
    }

    public function testDirectEditorIsRegisteredBeforeDefaultPriority(): void {
        $this->assertGreaterThan(0, Application::DIRECT_EDITOR_PRIORITY);
    }
}
