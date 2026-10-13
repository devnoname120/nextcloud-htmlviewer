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

namespace OCA\HtmlViewer\Tests\Unit\Settings;

use OCA\HtmlViewer\AppInfo\Application;
use OCA\HtmlViewer\Dto\AppSettings;
use OCA\HtmlViewer\Dto\IframePolicy;
use OCA\HtmlViewer\Service\AssetServiceInterface;
use OCA\HtmlViewer\Service\SettingsServiceInterface;
use OCA\HtmlViewer\Settings\AdminSettings;
use OCP\AppFramework\Services\IInitialState;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AdminSettings::class)]
class AdminSettingsTest extends TestCase {

    public function testFormProvidesSettingsAndLoadsAssets(): void {
        $appSettings = new AppSettings(true, false, true, 64, 16, 5, 'csp', null);
        $defaultSettings = new AppSettings(false, false, true, 32, 16, 5, null, null);
        $defaultPolicy = new IframePolicy('default-csp', '');
        $defaultJsPolicy = new IframePolicy('js-csp', 'allow-scripts');

        $settings = $this->createStub(SettingsServiceInterface::class);
        $settings->method('getAppSettings')->willReturn($appSettings);
        $settings->method('getDefaultAppSettings')->willReturn($defaultSettings);
        $settings->method('getDefaultIframePolicy')->willReturnMap([[false, $defaultPolicy], [true, $defaultJsPolicy]]);

        $state = [];
        $initialState = $this->createStub(IInitialState::class);
        $initialState->method('provideInitialState')
                     ->willReturnCallback(function (string $key, mixed $value) use (&$state) {
                         $state[ $key ] = $value;
                     });

        $assets = $this->createMock(AssetServiceInterface::class);
        $assets->expects($this->once())->method('loadAdminSettingsAssets');

        $response = (new AdminSettings($settings, $initialState, $assets))->getForm();

        $this->assertSame(Application::APP_ID, $response->getApp());
        $this->assertSame('adminSettings', $response->getTemplateName());
        $this->assertSame(
            [
                'appSettings'           => $appSettings,
                'defaultAppSettings'    => $defaultSettings,
                'defaultIframePolicy'   => $defaultPolicy,
                'defaultJsIframePolicy' => $defaultJsPolicy,
            ],
            $state
        );
    }

    public function testSectionAndPriority(): void {
        $settings = new AdminSettings(
            $this->createStub(SettingsServiceInterface::class),
            $this->createStub(IInitialState::class),
            $this->createStub(AssetServiceInterface::class),
        );

        $this->assertSame('additional', $settings->getSection());
        $this->assertGreaterThanOrEqual(0, $settings->getPriority());
        $this->assertLessThanOrEqual(100, $settings->getPriority());
    }
}
