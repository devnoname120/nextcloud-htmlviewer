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
use OCA\HtmlViewer\Dto\AppSettings;
use OCA\HtmlViewer\Dto\FileSizeLimits;
use OCA\HtmlViewer\Dto\IframePolicy;
use OCA\HtmlViewer\Service\SettingsServiceInterface;
use OCA\HtmlViewer\Service\ViewerStateService;
use OCP\AppFramework\Services\IInitialState;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(ViewerStateService::class)]
#[AllowMockObjectsWithoutExpectations]
class ViewerStateServiceTest extends TestCase {

    protected const SANDBOX = 'allow-scripts allow-modals';

    public function testProvidesFileSizeLimits(): void {
        $state = $this->provideState();

        $this->assertSame(300, $state['maxSize']);
        $this->assertSame(200, $state['maxEditorSize']);
        $this->assertSame(100, $state['maxCodeEditorSize']);
    }

    public function testProvidesSafeDefaults(): void {
        $state = $this->provideState(policy: new IframePolicy('default-csp', ''));

        $this->assertFalse($state['allowJs']);
        $this->assertArrayNotHasKey('nonce', $state);
        $this->assertSame('default-csp', $state['csp']);
        $this->assertSame('', $state['sandbox']);
        $this->assertFalse($state['newTabLinks']);
    }

    public function testProvidesJavaScriptSettings(): void {
        $state = $this->provideState(allowJs: true);

        $this->assertTrue($state['allowJs']);
        $this->assertSame('nonce-value', $state['nonce']);
        $this->assertSame('csp', $state['csp']);
        $this->assertSame(self::SANDBOX, $state['sandbox']);
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
        $state = $this->provideState(newTabLinks: true, policy: new IframePolicy('csp', $sandbox));

        $this->assertTrue($state['newTabLinks']);
        $this->assertSame($expected, $state['sandbox']);
    }

    public function testWarningIsShownForPublicShares(): void {
        $settings = $this->createSettings();
        $settings->expects($this->never())->method('isScriptWarningDisabled');

        $this->assertFalse($this->provideState(userId: null, settings: $settings)['disableWarning']);
    }

    public static function warningSettings(): array {
        return [
            'disabled' => [true],
            'enabled'  => [false],
        ];
    }

    #[DataProvider('warningSettings')]
    public function testWarningSettingOfUser(bool $disabled): void {
        $settings = $this->createSettings();
        $settings->expects($this->once())
                 ->method('isScriptWarningDisabled')
                 ->with('alice')
                 ->willReturn($disabled);

        $this->assertSame($disabled, $this->provideState(userId: 'alice', settings: $settings)['disableWarning']);
    }

    /**
     * @return array<string, mixed>
     */
    protected function provideState(
        bool                                       $allowJs = false,
        bool                                       $newTabLinks = false,
        ?IframePolicy                              $policy = null,
        ?string                                    $userId = 'alice',
        (SettingsServiceInterface&MockObject)|null $settings = null,
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

        $settings ??= $this->createSettings();
        $settings->method('getAppSettings')->willReturn(new AppSettings($newTabLinks, $allowJs, true, 32, 16, 5, null, null));
        $settings->method('getIframePolicy')->willReturn($policy ?? new IframePolicy('csp', self::SANDBOX));

        $service = new ViewerStateService($nonceManager, $initialState, $settings);
        $service->provideViewerState($userId);

        return $state;
    }

    protected function createSettings(): SettingsServiceInterface&MockObject {
        $settings = $this->createMock(SettingsServiceInterface::class);
        $settings->method('getFileSizeLimits')->willReturn(new FileSizeLimits(300, 200, 100));

        return $settings;
    }
}
