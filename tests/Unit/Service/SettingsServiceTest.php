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

namespace OCA\HtmlViewer\Tests\Unit\Service;

use OCA\HtmlViewer\Dto\AppSettings;
use OCA\HtmlViewer\Dto\AppSettingValue;
use OCA\HtmlViewer\Dto\FileSizeLimits;
use OCA\HtmlViewer\Dto\IframePolicy;
use OCA\HtmlViewer\Service\SettingsService;
use OCA\HtmlViewer\Settings\AppSetting;
use OCP\AppFramework\Services\IAppConfig;
use OCP\Exceptions\AppConfigTypeConflictException;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(SettingsService::class)]
class SettingsServiceTest extends TestCase {

    protected const MB = 1024 * 1024;

    public static function fileSizeLimits(): array {
        return [
            'defaults'                        => [[], 32, 16, 5],
            'custom limits'                   => [['maxSize' => 64, 'maxEditorSize' => 8, 'maxCodeEditorSize' => 2], 64, 8, 2],
            'negative values'                 => [['maxSize' => -1, 'maxEditorSize' => -5, 'maxCodeEditorSize' => -10], 0, 0, 0],
            'editor limited by viewer'        => [['maxSize' => 10, 'maxEditorSize' => 20], 10, 10, 5],
            'code editor limited by editor'   => [['maxEditorSize' => 3, 'maxCodeEditorSize' => 4], 32, 3, 3],
            'viewer limit applies to all'     => [['maxSize' => 4], 4, 4, 4],
            'disabled viewer disables editor' => [['maxSize' => 0], 0, 0, 0],
        ];
    }

    /**
     * @param array<string, int> $config
     */
    #[DataProvider('fileSizeLimits')]
    public function testGetFileSizeLimits(array $config, int $maxSize, int $maxEditorSize, int $maxCodeEditorSize): void {
        $appConfig = $this->createStub(IAppConfig::class);
        $appConfig->method('getAppValueInt')
                  ->willReturnCallback(fn(string $key, int $default = 0) => $config[ $key ] ?? $default);

        $limits = (new SettingsService($appConfig))->getFileSizeLimits();

        $this->assertEquals(
            new FileSizeLimits($maxSize * self::MB, $maxEditorSize * self::MB, $maxCodeEditorSize * self::MB),
            $limits
        );
    }

    public function testDirectEditingIsEnabledByDefault(): void {
        $appConfig = $this->createStub(IAppConfig::class);
        $appConfig->method('getAppValueBool')
                  ->willReturnCallback(fn(string $key, bool $default = false) => $default);

        $this->assertTrue((new SettingsService($appConfig))->isDirectEditingEnabled());
    }

    public function testDirectEditingCanBeDisabled(): void {
        $appConfig = $this->createMock(IAppConfig::class);
        $appConfig->expects($this->once())
                  ->method('getAppValueBool')
                  ->with('directEditing', true)
                  ->willReturn(false);

        $this->assertFalse((new SettingsService($appConfig))->isDirectEditingEnabled());
    }

    public function testGetAppSettingsReturnsDefaultsIfNothingIsConfigured(): void {
        $service = new SettingsService($this->createConfig());

        $this->assertEquals(new AppSettings(false, false, true, 32, 16, 5, null, null), $service->getAppSettings());
        $this->assertEquals($service->getDefaultAppSettings(), $service->getAppSettings());
    }

    public function testGetAppSettingsReturnsConfiguredValues(): void {
        $config = $this->createConfig(
            ['newTabLinks' => true, 'allowJs' => true, 'directEditing' => false, 'maxSize' => 64, 'maxEditorSize' => 100, 'maxCodeEditorSize' => 1, 'csp' => "default-src 'none'", 'sandbox' => '']
        );

        $this->assertEquals(
            new AppSettings(true, true, false, 64, 100, 1, "default-src 'none'", ''),
            (new SettingsService($config))->getAppSettings()
        );
    }

    public static function conflictingTypes(): array {
        return [
            'int stored as string'    => [['maxSize' => '64'], 'maxSize', 64],
            'invalid int'             => [['maxSize' => 'large'], 'maxSize', 32],
            'bool stored as string'   => [['allowJs' => 'true'], 'allowJs', true],
            'bool stored as int'      => [['directEditing' => 0], 'directEditing', false],
            'invalid bool'            => [['directEditing' => 'maybe'], 'directEditing', true],
            'string stored as int'    => [['sandbox' => 5], 'sandbox', '5'],
        ];
    }

    /**
     * @param array<string, mixed> $stored
     */
    #[DataProvider('conflictingTypes')]
    public function testValuesStoredWithOtherTypeAreConverted(array $stored, string $key, bool|int|string $expected): void {
        $conflict = fn() => throw new AppConfigTypeConflictException();
        $config = $this->createStub(IAppConfig::class);
        $config->method('hasAppKey')->willReturnCallback(fn(string $key) => array_key_exists($key, $stored));
        $config->method('getAppValueBool')->willReturnCallback(fn(string $key, bool $default = false) => array_key_exists($key, $stored) ? $conflict():$default);
        $config->method('getAppValueInt')->willReturnCallback(fn(string $key, int $default = 0) => array_key_exists($key, $stored) ? $conflict():$default);
        $config->method('getAppValueString')->willReturnCallback($conflict);
        $config->method('getAllAppValues')->willReturnCallback(fn(string $key) => array_intersect_key($stored, [$key => true]));

        $this->assertSame($expected, (new SettingsService($config))->getAppSettings()->{$key});
    }

    public static function appSettingValues(): array {
        return [
            'bool'    => [AppSetting::AllowJs, true, 'setAppValueBool'],
            'int'     => [AppSetting::MaxSize, 64, 'setAppValueInt'],
            'csp'     => [AppSetting::Csp, "default-src 'none'", 'setAppValueString'],
            'sandbox' => [AppSetting::Sandbox, 'allow-forms', 'setAppValueString'],
        ];
    }

    #[DataProvider('appSettingValues')]
    public function testSetAppSettingStoresTypedValue(AppSetting $setting, bool|int|string $value, string $method): void {
        $config = $this->createMock(IAppConfig::class);
        $config->expects($this->once())->method($method)->with($setting->value, $value)->willReturn(true);
        $config->expects($this->never())->method('deleteAppValue');

        (new SettingsService($config))->setAppSetting(new AppSettingValue($setting, $value));
    }

    public function testSetAppSettingReplacesValueWithOtherType(): void {
        $calls = 0;
        $config = $this->createMock(IAppConfig::class);
        $config->expects($this->exactly(2))
               ->method('setAppValueInt')
               ->with('maxSize', 64)
               ->willReturnCallback(function () use (&$calls) {
                   if($calls++ === 0) {
                       throw new AppConfigTypeConflictException();
                   }

                   return true;
               });
        $config->expects($this->once())->method('deleteAppValue')->with('maxSize');

        (new SettingsService($config))->setAppSetting(new AppSettingValue(AppSetting::MaxSize, 64));
    }

    public function testEmptyPolicyRestoresDefault(): void {
        $config = $this->createMock(IAppConfig::class);
        $config->expects($this->once())->method('deleteAppValue')->with('csp');
        $config->expects($this->never())->method('setAppValueString');

        (new SettingsService($config))->setAppSetting(new AppSettingValue(AppSetting::Csp, '  '));
    }

    public function testResetAppSettingDeletesValue(): void {
        $config = $this->createMock(IAppConfig::class);
        $config->expects($this->once())->method('deleteAppValue')->with('newTabLinks');

        (new SettingsService($config))->resetAppSetting(AppSetting::NewTabLinks);
    }

    public static function iframePolicies(): array {
        return [
            'defaults'                 => [[], 'csp', 'sandbox', false],
            'javascript defaults'      => [['allowJs' => true], 'csp', 'sandbox', true],
            'custom csp'               => [['csp' => 'custom'], 'custom', 'sandbox', false],
            'custom sandbox'           => [['allowJs' => true, 'sandbox' => 'allow-forms'], 'csp', 'allow-forms', true],
            'empty values are kept'    => [['csp' => '', 'sandbox' => ''], '', '', false],
        ];
    }

    /**
     * @param array<string, bool|string> $values
     */
    #[DataProvider('iframePolicies')]
    public function testGetIframePolicy(array $values, string $csp, string $sandbox, bool $defaultForJs): void {
        $service = new SettingsService($this->createConfig($values));
        $default = $service->getDefaultIframePolicy($defaultForJs);

        $policy = $service->getIframePolicy();

        $this->assertSame($csp === 'csp' ? $default->csp:$csp, $policy->csp);
        $this->assertSame($sandbox === 'sandbox' ? $default->sandbox:$sandbox, $policy->sandbox);
    }

    public function testDefaultIframePolicies(): void {
        $service = new SettingsService($this->createStub(IAppConfig::class));

        $this->assertEquals(
            new IframePolicy("default-src blob: data: ; style-src 'unsafe-inline' blob: data:", ''),
            $service->getDefaultIframePolicy(false)
        );
        $this->assertEquals(
            new IframePolicy(
                "default-src 'unsafe-eval' 'unsafe-inline' 'wasm-unsafe-eval' blob: data:",
                'allow-scripts allow-presentation allow-modals allow-downloads'
            ),
            $service->getDefaultIframePolicy(true)
        );
    }

    public static function warningSettings(): array {
        return [
            'not set'  => [null, false],
            'disabled' => ['1', true],
            'enabled'  => ['0', false],
            'invalid'  => ['yes', false],
        ];
    }

    #[DataProvider('warningSettings')]
    public function testIsScriptWarningDisabled(?string $value, bool $expected): void {
        $config = $this->createMock(IAppConfig::class);
        $config->expects($this->once())
               ->method('getUserValue')
               ->with('alice', 'disableWarning', '0')
               ->willReturnCallback(fn(string $userId, string $key, string $default) => $value ?? $default);

        $this->assertSame($expected, (new SettingsService($config))->isScriptWarningDisabled('alice'));
    }

    public function testDisableScriptWarningStoresUserValue(): void {
        $config = $this->createMock(IAppConfig::class);
        $config->expects($this->once())
               ->method('setUserValue')
               ->with('alice', 'disableWarning', '1');

        (new SettingsService($config))->disableScriptWarning('alice');
    }

    /**
     * @param array<string, bool|int|string> $values
     */
    protected function createConfig(array $values = []): IAppConfig&Stub {
        $config = $this->createStub(IAppConfig::class);
        $config->method('hasAppKey')
               ->willReturnCallback(fn(string $key) => array_key_exists($key, $values));
        $config->method('getAppValueBool')
               ->willReturnCallback(fn(string $key, bool $default = false) => $values[ $key ] ?? $default);
        $config->method('getAppValueInt')
               ->willReturnCallback(fn(string $key, int $default = 0) => $values[ $key ] ?? $default);
        $config->method('getAppValueString')
               ->willReturnCallback(fn(string $key, string $default = '') => $values[ $key ] ?? $default);

        return $config;
    }
}
