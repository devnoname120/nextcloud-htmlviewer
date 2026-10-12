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

use OC\Security\CSP\ContentSecurityPolicyNonceManager;
use OCA\HtmlViewer\Dto\FileSizeLimits;
use OCA\HtmlViewer\Service\SettingsServiceInterface;
use OCA\HtmlViewer\Service\ViewerStateService;
use OCP\AppFramework\Services\IAppConfig;
use OCP\AppFramework\Services\IInitialState;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(ViewerStateService::class)]
#[AllowMockObjectsWithoutExpectations]
class ViewerStateServiceTest extends TestCase {

    protected const DEFAULT_CSP = "default-src blob: data: ; style-src 'unsafe-inline' blob: data:";

    protected const DEFAULT_JS_CSP = "default-src 'unsafe-eval' 'unsafe-inline' 'wasm-unsafe-eval' blob: data:";

    protected const DEFAULT_JS_SANDBOX = 'allow-scripts allow-presentation allow-modals allow-downloads';

    public function testProvidesFileSizeLimits(): void {
        $state = $this->provideState();

        $this->assertSame(300, $state['maxSize']);
        $this->assertSame(200, $state['maxEditorSize']);
        $this->assertSame(100, $state['maxCodeEditorSize']);
    }

    public function testProvidesSafeDefaults(): void {
        $state = $this->provideState();

        $this->assertFalse($state['allowJs']);
        $this->assertArrayNotHasKey('nonce', $state);
        $this->assertSame(self::DEFAULT_CSP, $state['csp']);
        $this->assertSame('', $state['sandbox']);
        $this->assertFalse($state['newTabLinks']);
    }

    public function testProvidesJavaScriptSettings(): void {
        $state = $this->provideState(bools: ['allowJs' => true]);

        $this->assertTrue($state['allowJs']);
        $this->assertSame('nonce-value', $state['nonce']);
        $this->assertSame(self::DEFAULT_JS_CSP, $state['csp']);
        $this->assertSame(self::DEFAULT_JS_SANDBOX, $state['sandbox']);
    }

    public function testCustomCspAndSandboxReplaceDefaults(): void {
        $state = $this->provideState(
            bools  : ['allowJs' => true],
            strings: ['csp' => "default-src 'none'", 'sandbox' => 'allow-forms']
        );

        $this->assertSame("default-src 'none'", $state['csp']);
        $this->assertSame('allow-forms', $state['sandbox']);
    }

    public static function newTabLinkSandboxes(): array {
        return [
            'empty sandbox'         => ['', 'allow-popups allow-popups-to-escape-sandbox'],
            'existing permissions'  => ['allow-forms', 'allow-forms allow-popups allow-popups-to-escape-sandbox'],
            'already allowed'       => ['allow-popups-to-escape-sandbox allow-popups', 'allow-popups-to-escape-sandbox allow-popups'],
            'partially allowed'     => ['allow-popups', 'allow-popups allow-popups-to-escape-sandbox'],
            'irregular whitespace'  => ["  allow-forms \t allow-modals\n", 'allow-forms allow-modals allow-popups allow-popups-to-escape-sandbox'],
        ];
    }

    #[DataProvider('newTabLinkSandboxes')]
    public function testNewTabLinksAddPopupPermissions(string $sandbox, string $expected): void {
        $state = $this->provideState(bools: ['newTabLinks' => true], strings: ['sandbox' => $sandbox]);

        $this->assertTrue($state['newTabLinks']);
        $this->assertSame($expected, $state['sandbox']);
    }

    public function testNewTabLinksExtendJavaScriptSandbox(): void {
        $state = $this->provideState(bools: ['allowJs' => true, 'newTabLinks' => true]);

        $this->assertSame(self::DEFAULT_JS_SANDBOX.' allow-popups allow-popups-to-escape-sandbox', $state['sandbox']);
    }

    public function testWarningIsShownForPublicShares(): void {
        $config = $this->createConfig();
        $config->expects($this->never())->method('getUserValue');

        $this->assertFalse($this->provideState(userId: null, config: $config)['disableWarning']);
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
    public function testWarningSettingOfUser(?string $value, bool $expected): void {
        $config = $this->createConfig();
        $config->expects($this->once())
               ->method('getUserValue')
               ->with('alice', 'disableWarning', '0')
               ->willReturnCallback(fn(string $userId, string $key, string $default) => $value ?? $default);

        $this->assertSame($expected, $this->provideState(userId: 'alice', config: $config)['disableWarning']);
    }

    /**
     * @param array<string, bool>   $bools
     * @param array<string, string> $strings
     *
     * @return array<string, mixed>
     */
    protected function provideState(
        array       $bools = [],
        array       $strings = [],
        ?string     $userId = 'alice',
        ?IAppConfig $config = null,
    ): array {
        $state = [];
        $initialState = $this->createStub(IInitialState::class);
        $initialState->method('provideInitialState')
                     ->willReturnCallback(function (string $key, mixed $value) use (&$state) {
                         $this->assertArrayNotHasKey($key, $state, "Initial state {$key} provided twice");
                         $state[ $key ] = $value;
                     });

        $nonceManager = $this->createStub(ContentSecurityPolicyNonceManager::class);
        $nonceManager->method('getNonce')->willReturn('nonce-value');

        $settings = $this->createStub(SettingsServiceInterface::class);
        $settings->method('getFileSizeLimits')->willReturn(new FileSizeLimits(300, 200, 100));

        $service = new ViewerStateService($nonceManager, $initialState, $config ?? $this->createConfig($bools, $strings), $settings);
        $service->provideViewerState($userId);

        return $state;
    }

    /**
     * @param array<string, bool>   $bools
     * @param array<string, string> $strings
     */
    protected function createConfig(array $bools = [], array $strings = []): IAppConfig&MockObject {
        $config = $this->createMock(IAppConfig::class);
        $config->method('getAppValueBool')
               ->willReturnCallback(fn(string $key, bool $default = false) => $bools[ $key ] ?? $default);
        $config->method('getAppValueString')
               ->willReturnCallback(fn(string $key, string $default = '') => $strings[ $key ] ?? $default);

        return $config;
    }
}
