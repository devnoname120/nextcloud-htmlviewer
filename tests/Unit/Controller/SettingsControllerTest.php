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
use OCA\HtmlViewer\Dto\AppSettings;
use OCA\HtmlViewer\Dto\AppSettingValue;
use OCA\HtmlViewer\Service\SettingsServiceInterface;
use OCA\HtmlViewer\Settings\AppSetting;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\IRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

#[CoversClass(SettingsController::class)]
class SettingsControllerTest extends TestCase {

    public function testDisableWarningStoresSettingOfCurrentUser(): void {
        $settings = $this->createMock(SettingsServiceInterface::class);
        $settings->expects($this->once())
                 ->method('disableScriptWarning')
                 ->with('alice');

        $response = $this->createController($settings)->disableWarning();

        $this->assertSame(Http::STATUS_OK, $response->getStatus());
        $this->assertSame([], $response->getData());
    }

    public function testUpdateAppSettingStoresValueAndReturnsSettings(): void {
        $appSettings = new AppSettings(true, false, true, 32, 16, 5, null, null);

        $settings = $this->createMock(SettingsServiceInterface::class);
        $settings->expects($this->once())
                 ->method('setAppSetting')
                 ->with(new AppSettingValue(AppSetting::NewTabLinks, true));
        $settings->method('getAppSettings')->willReturn($appSettings);

        $response = $this->createController($settings)->updateAppSetting('newTabLinks', true);

        $this->assertSame(Http::STATUS_OK, $response->getStatus());
        $this->assertSame($appSettings, $response->getData());
    }

    public function testUpdateAppSettingRejectsUnknownSetting(): void {
        $settings = $this->createMock(SettingsServiceInterface::class);
        $settings->expects($this->never())->method('setAppSetting');

        $response = $this->createController($settings)->updateAppSetting('disableWarning', true);

        $this->assertSame(Http::STATUS_NOT_FOUND, $response->getStatus());
        $this->assertSame([], $response->getData());
    }

    public static function invalidValues(): array {
        return [
            'string for bool' => ['allowJs', 'true'],
            'missing value'   => ['allowJs', null],
            'negative size'   => ['maxSize', -1],
            'string for size' => ['maxSize', '5'],
            'invalid csp'     => ['csp', "default-src 'none'\x01"],
            'invalid sandbox' => ['sandbox', 'allow-forms; script'],
        ];
    }

    #[DataProvider('invalidValues')]
    public function testUpdateAppSettingRejectsInvalidValues(string $key, mixed $value): void {
        $settings = $this->createMock(SettingsServiceInterface::class);
        $settings->expects($this->never())->method('setAppSetting');

        $response = $this->createController($settings)->updateAppSetting($key, $value);

        $this->assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
        $this->assertSame([], $response->getData());
    }

    public function testResetAppSettingDeletesValueAndReturnsSettings(): void {
        $appSettings = new AppSettings(false, false, true, 32, 16, 5, null, null);

        $settings = $this->createMock(SettingsServiceInterface::class);
        $settings->expects($this->once())
                 ->method('resetAppSetting')
                 ->with(AppSetting::Csp);
        $settings->method('getAppSettings')->willReturn($appSettings);

        $response = $this->createController($settings)->resetAppSetting('csp');

        $this->assertSame(Http::STATUS_OK, $response->getStatus());
        $this->assertSame($appSettings, $response->getData());
    }

    public function testResetAppSettingRejectsUnknownSetting(): void {
        $settings = $this->createMock(SettingsServiceInterface::class);
        $settings->expects($this->never())->method('resetAppSetting');

        $response = $this->createController($settings)->resetAppSetting('unknown');

        $this->assertSame(Http::STATUS_NOT_FOUND, $response->getStatus());
    }

    public static function adminMethods(): array {
        return [
            'update' => ['updateAppSetting'],
            'reset'  => ['resetAppSetting'],
        ];
    }

    #[DataProvider('adminMethods')]
    public function testAppSettingsRequireAdmin(string $method): void {
        $this->assertSame([], (new ReflectionMethod(SettingsController::class, $method))->getAttributes(NoAdminRequired::class));
    }

    protected function createController(SettingsServiceInterface $settings): SettingsController {
        return new SettingsController('alice', $this->createStub(IRequest::class), $settings);
    }
}
