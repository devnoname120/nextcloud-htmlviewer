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

namespace OCA\HtmlViewer\Tests\Unit\Dto;

use OCA\HtmlViewer\Dto\AppSettingValue;
use OCA\HtmlViewer\Exception\InvalidSettingValueException;
use OCA\HtmlViewer\Settings\AppSetting;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(AppSettingValue::class)]
class AppSettingValueTest extends TestCase {

    public static function validValues(): array {
        return [
            'enable'               => [AppSetting::AllowJs, true, true],
            'disable'              => [AppSetting::DirectEditing, false, false],
            'zero size'            => [AppSetting::MaxSize, 0, 0],
            'maximum size'         => [AppSetting::MaxCodeEditorSize, AppSettingValue::MAX_MEGABYTES, AppSettingValue::MAX_MEGABYTES],
            'csp'                  => [AppSetting::Csp, "default-src 'self' https://a.example; img-src data:", "default-src 'self' https://a.example; img-src data:"],
            'csp whitespace'       => [AppSetting::Csp, "  default-src\t'none' ;\n img-src * ", "default-src 'none' ; img-src *"],
            'csp line breaks'      => [AppSetting::Csp, "default-src 'none'\r\nX-Header: 1", "default-src 'none' X-Header: 1"],
            'empty csp'            => [AppSetting::Csp, '', ''],
            'sandbox'              => [AppSetting::Sandbox, 'allow-forms allow-top-navigation-by-user-activation', 'allow-forms allow-top-navigation-by-user-activation'],
            'sandbox whitespace'   => [AppSetting::Sandbox, " allow-forms \n allow-modals ", 'allow-forms allow-modals'],
            'empty sandbox'        => [AppSetting::Sandbox, '   ', ''],
        ];
    }

    #[DataProvider('validValues')]
    public function testValidValues(AppSetting $setting, mixed $value, bool|int|string $expected): void {
        $settingValue = new AppSettingValue($setting, $value);

        $this->assertSame($setting, $settingValue->setting);
        $this->assertSame($expected, $settingValue->value);
    }

    public static function invalidValues(): array {
        return [
            'string for bool'     => [AppSetting::NewTabLinks, 'true'],
            'int for bool'        => [AppSetting::AllowJs, 1],
            'null for bool'       => [AppSetting::DirectEditing, null],
            'negative size'       => [AppSetting::MaxSize, -1],
            'too large size'      => [AppSetting::MaxEditorSize, AppSettingValue::MAX_MEGABYTES + 1],
            'float size'          => [AppSetting::MaxSize, 1.5],
            'string size'         => [AppSetting::MaxSize, '16'],
            'bool for csp'        => [AppSetting::Csp, false],
            'array for csp'       => [AppSetting::Csp, ['default-src']],
            'csp control chars'   => [AppSetting::Csp, "default-src\x01 'none'"],
            'csp unicode'         => [AppSetting::Csp, 'img-src https://exämple.com'],
            'csp too long'        => [AppSetting::Csp, str_repeat('a', AppSettingValue::MAX_POLICY_LENGTH + 1)],
            'sandbox other token' => [AppSetting::Sandbox, 'allow-forms scripts'],
            'sandbox quotes'      => [AppSetting::Sandbox, 'allow-forms" onload="alert(1)'],
            'sandbox uppercase'   => [AppSetting::Sandbox, 'ALLOW-FORMS'],
        ];
    }

    #[DataProvider('invalidValues')]
    public function testInvalidValues(AppSetting $setting, mixed $value): void {
        $this->expectException(InvalidSettingValueException::class);

        new AppSettingValue($setting, $value);
    }
}
