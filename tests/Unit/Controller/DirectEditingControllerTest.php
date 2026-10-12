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

use Exception;
use OCA\HtmlViewer\Controller\DirectEditingController;
use OCA\HtmlViewer\Dto\FileContent;
use OCA\HtmlViewer\Exception\FileTooLargeException;
use OCA\HtmlViewer\Exception\InvalidTokenException;
use OCA\HtmlViewer\Service\DirectEditingServiceInterface;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\AppFramework\Http\Response;
use OCP\Files\NotPermittedException;
use OCP\IRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

#[CoversClass(DirectEditingController::class)]
class DirectEditingControllerTest extends TestCase {

    protected DirectEditingServiceInterface&MockObject $service;

    protected DirectEditingController $controller;

    protected function setUp(): void {
        $request = $this->createStub(IRequest::class);
        $request->method('getHeader')->willReturnMap([['X-HtmlViewer-Token', " token-value\n"]]);

        $this->service = $this->createMock(DirectEditingServiceInterface::class);
        $this->controller = new DirectEditingController($request, $this->service);
    }

    public function testReadReturnsContentAsPlainText(): void {
        $this->service->expects($this->once())
                      ->method('readFile')
                      ->with('token-value')
                      ->willReturn(new FileContent('<script>alert(1)</script>'));

        $response = $this->controller->read();

        $this->assertInstanceOf(DataDisplayResponse::class, $response);
        $this->assertSame(Http::STATUS_OK, $response->getStatus());
        $this->assertSame('<script>alert(1)</script>', $response->getData());

        $headers = $this->headers($response);
        $this->assertSame('text/plain; charset=utf-8', $headers['Content-Type']);
        $this->assertSame('nosniff', $headers['X-Content-Type-Options']);
        $this->assertSame('no-store', $headers['Cache-Control']);
        $this->assertFalse($response->isThrottled());
    }

    public static function errors(): array {
        return [
            'invalid token'  => [new InvalidTokenException(), Http::STATUS_NOT_FOUND, true],
            'file too large' => [new FileTooLargeException(), Http::STATUS_REQUEST_ENTITY_TOO_LARGE, false],
            'not permitted'  => [new NotPermittedException(), Http::STATUS_FORBIDDEN, false],
        ];
    }

    #[DataProvider('errors')]
    public function testReadErrors(Exception $error, int $status, bool $throttled): void {
        $this->service->expects($this->once())->method('readFile')->willThrowException($error);

        $this->assertErrorResponse($this->controller->read(), $status, $throttled);
    }

    public function testWriteStoresContent(): void {
        $this->service->expects($this->once())
                      ->method('writeFile')
                      ->with('token-value', new FileContent('<p>New</p>'));

        $response = $this->controller->write('<p>New</p>');

        $this->assertSame(Http::STATUS_NO_CONTENT, $response->getStatus());
        $this->assertFalse($response->isThrottled());
    }

    public function testWriteAcceptsEmptyContent(): void {
        $this->service->expects($this->once())
                      ->method('writeFile')
                      ->with('token-value', new FileContent(''));

        $this->assertSame(Http::STATUS_NO_CONTENT, $this->controller->write('')->getStatus());
    }

    public static function invalidContent(): array {
        return [
            'missing' => [null],
            'number'  => [42],
            'boolean' => [true],
            'array'   => [['<p>New</p>']],
        ];
    }

    #[DataProvider('invalidContent')]
    public function testWriteRejectsInvalidContent(mixed $content): void {
        $this->service->expects($this->never())->method('writeFile');

        $this->assertSame(Http::STATUS_BAD_REQUEST, $this->controller->write($content)->getStatus());
    }

    #[DataProvider('errors')]
    public function testWriteErrors(Exception $error, int $status, bool $throttled): void {
        $this->service->expects($this->once())->method('writeFile')->willThrowException($error);

        $this->assertErrorResponse($this->controller->write('<p>New</p>'), $status, $throttled);
    }

    protected function assertErrorResponse(Response $response, int $status, bool $throttled): void {
        $this->assertSame($status, $response->getStatus());
        $this->assertSame($throttled, $response->isThrottled());
        if($throttled) {
            $this->assertSame(['action' => 'htmlviewer_direct_editing'], $response->getThrottleMetadata());
        }
        $this->assertNotInstanceOf(DataDisplayResponse::class, $response);
    }

    /**
     * Response::getHeaders() needs a running server, so the headers set by the controller are read directly
     *
     * @return array<string, string>
     */
    protected function headers(Response $response): array {
        return (new ReflectionProperty(Response::class, 'headers'))->getValue($response);
    }
}
