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

use OCA\HtmlViewer\Dto\AppSettings;
use OCA\HtmlViewer\Listeners\CSPListener;
use OCA\HtmlViewer\Service\SettingsServiceInterface;
use OCP\AppFramework\Http\EmptyContentSecurityPolicy;
use OCP\EventDispatcher\Event;
use OCP\Security\CSP\AddContentSecurityPolicyEvent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

#[CoversClass(CSPListener::class)]
class CSPListenerTest extends TestCase {

    public function testIgnoresOtherEvents(): void {
        $settings = $this->createMock(SettingsServiceInterface::class);
        $settings->expects($this->never())->method($this->anything());

        (new CSPListener($settings))->handle(new Event());
    }

    public function testDefaultPolicyAllowsBlobFramesAndDataMedia(): void {
        $csp = $this->handle();

        $this->assertSame(['blob:'], $this->domains($csp, 'allowedFrameDomains'));
        $this->assertSame(['data:'], $this->domains($csp, 'allowedMediaDomains'));
        $this->assertNotTrue($this->property($csp, 'evalWasmAllowed'));

        foreach(['allowedScriptDomains', 'allowedStyleDomains', 'allowedFontDomains', 'allowedImageDomains', 'allowedConnectDomains', 'allowedObjectDomains', 'allowedWorkerSrcDomains', 'allowedFormActionDomains'] as $property) {
            $this->assertSame([], $this->domains($csp, $property), $property);
        }
    }

    public function testJavaScriptAllowsEval(): void {
        $this->assertTrue($this->property($this->handle(allowJs: true), 'evalWasmAllowed'));
    }

    public static function evalPolicies(): array {
        return [
            'unsafe eval'      => ["script-src 'unsafe-eval'", true],
            'wasm unsafe eval' => ["script-src 'wasm-unsafe-eval'", true],
            'eval in domain'   => ['img-src https://medieval.example', false],
            'unquoted keyword' => ['script-src unsafe-eval', false],
        ];
    }

    #[DataProvider('evalPolicies')]
    public function testCustomPolicyAllowsEvalOnlyWithEvalKeyword(string $customCsp, bool $expected): void {
        $this->assertSame($expected, $this->property($this->handle($customCsp), 'evalWasmAllowed') === true);
    }

    public static function directives(): array {
        return [
            'script-src'  => ['script-src', 'allowedScriptDomains'],
            'style-src'   => ['style-src', 'allowedStyleDomains'],
            'font-src'    => ['font-src', 'allowedFontDomains'],
            'img-src'     => ['img-src', 'allowedImageDomains'],
            'connect-src' => ['connect-src', 'allowedConnectDomains'],
            'media-src'   => ['media-src', 'allowedMediaDomains'],
            'object-src'  => ['object-src', 'allowedObjectDomains'],
            'frame-src'   => ['frame-src', 'allowedFrameDomains'],
            'worker-src'  => ['worker-src', 'allowedWorkerSrcDomains'],
            'form-action' => ['form-action', 'allowedFormActionDomains'],
        ];
    }

    #[DataProvider('directives')]
    public function testCustomDomainsAreAddedToDirective(string $directive, string $property): void {
        $csp = $this->handle("{$directive} https://a.example https://b.example");

        $this->assertContains('https://a.example', $this->domains($csp, $property));
        $this->assertContains('https://b.example', $this->domains($csp, $property));
    }

    public function testMultipleDirectivesAreParsed(): void {
        $csp = $this->handle('script-src https://a.example; img-src https://b.example;');

        $this->assertSame(['https://a.example'], $this->domains($csp, 'allowedScriptDomains'));
        $this->assertSame(['https://b.example'], $this->domains($csp, 'allowedImageDomains'));
    }

    public function testUnsupportedDirectivesAreIgnored(): void {
        $csp = $this->handle('default-src https://a.example; frame-ancestors https://b.example; sandbox allow-scripts');

        $this->assertSame([], $this->domains($csp, 'allowedScriptDomains'));
        $this->assertSame([], $this->domains($csp, 'allowedFrameAncestors'));
        $this->assertSame(['blob:'], $this->domains($csp, 'allowedFrameDomains'));
    }

    public static function ignoredSources(): array {
        return [
            'self'                => ["'self'"],
            'none'                => ["'none'"],
            'unsafe inline'       => ["'unsafe-inline'"],
            'unsafe eval'         => ["'unsafe-eval'"],
            'wasm unsafe eval'    => ["'wasm-unsafe-eval'"],
            'unsafe hashes'       => ["'unsafe-hashes'"],
            'strict dynamic'      => ["'strict-dynamic'"],
            'report sample'       => ["'report-sample'"],
            'speculation rules'   => ["'inline-speculation-rules'"],
            'blob'                => ['blob:'],
            'data'                => ['data:'],
            'mediastream'         => ['mediastream:'],
            'filesystem'          => ['filesystem:'],
            'nonce'               => ["'nonce-cmFuZG9t'"],
            'sha256'              => ["'sha256-B2yPHKaXnvFWtRChIbabYmUBFZdVfKKXHbWtWidDVF8='"],
            'sha384'              => ["'sha384-abc'"],
            'sha512'              => ["'sha512-abc'"],
            'unquoted nonce'      => ['nonce-cmFuZG9t'],
            'unquoted sha256'     => ['sha256-abc'],
        ];
    }

    #[DataProvider('ignoredSources')]
    public function testKeywordsNoncesAndHashesAreIgnored(string $source): void {
        $csp = $this->handle("script-src {$source} https://a.example");

        $this->assertSame(['https://a.example'], $this->domains($csp, 'allowedScriptDomains'));
    }

    public function testIrregularWhitespaceDoesNotAddEmptySources(): void {
        $csp = $this->handle(" script-src   https://a.example\thttps://b.example ;; img-src\nhttps://c.example ; ");

        $this->assertSame(['https://a.example', 'https://b.example'], $this->domains($csp, 'allowedScriptDomains'));
        $this->assertSame(['https://c.example'], $this->domains($csp, 'allowedImageDomains'));
    }

    public function testDefaultPolicyIsNotAddedToNextcloud(): void {
        $csp = $this->handle(null);

        $this->assertSame(['blob:'], $this->domains($csp, 'allowedFrameDomains'));
        $this->assertSame([], $this->domains($csp, 'allowedStyleDomains'));
    }

    protected function handle(?string $customCsp = '', bool $allowJs = false): EmptyContentSecurityPolicy {
        $settings = $this->createStub(SettingsServiceInterface::class);
        $settings->method('getAppSettings')->willReturn(new AppSettings(false, $allowJs, true, 32, 16, 5, $customCsp, null));

        $policy = null;
        $event = $this->createMock(AddContentSecurityPolicyEvent::class);
        $event->expects($this->once())
              ->method('addPolicy')
              ->willReturnCallback(function (EmptyContentSecurityPolicy $csp) use (&$policy) {
                  $policy = $csp;
              });

        (new CSPListener($settings))->handle($event);

        $this->assertInstanceOf(EmptyContentSecurityPolicy::class, $policy);

        return $policy;
    }

    /**
     * @return string[]
     */
    protected function domains(EmptyContentSecurityPolicy $csp, string $property): array {
        return $this->property($csp, $property) ?? [];
    }

    protected function property(EmptyContentSecurityPolicy $csp, string $property): mixed {
        return (new ReflectionProperty(EmptyContentSecurityPolicy::class, $property))->getValue($csp);
    }
}
