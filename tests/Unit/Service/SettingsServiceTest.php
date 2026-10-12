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

use OCA\HtmlViewer\Dto\FileSizeLimits;
use OCA\HtmlViewer\Service\SettingsService;
use OCP\AppFramework\Services\IAppConfig;
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
}
