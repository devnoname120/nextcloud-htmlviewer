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

namespace OCA\HtmlViewer\Tests\Unit\Controller;

use OCA\HtmlViewer\Controller\SettingsController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Services\IAppConfig;
use OCP\IRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SettingsController::class)]
class SettingsControllerTest extends TestCase {

    public function testDisableWarningStoresSettingOfCurrentUser(): void {
        $config = $this->createMock(IAppConfig::class);
        $config->expects($this->once())
               ->method('setUserValue')
               ->with('alice', 'disableWarning', '1');

        $controller = new SettingsController('alice', $this->createStub(IRequest::class), $config);
        $response = $controller->disableWarning();

        $this->assertSame(Http::STATUS_OK, $response->getStatus());
        $this->assertSame([], $response->getData());
    }
}
