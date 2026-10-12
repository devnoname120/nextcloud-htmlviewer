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

namespace OCA\HtmlViewer\Tests\Unit\DirectEditing;

use OCA\HtmlViewer\DirectEditing\HtmlDirectEditor;
use OCA\HtmlViewer\Dto\DirectEditingFile;
use OCA\HtmlViewer\Exception\InvalidTokenException;
use OCA\HtmlViewer\Service\AssetServiceInterface;
use OCA\HtmlViewer\Service\DirectEditingServiceInterface;
use OCA\HtmlViewer\Service\ViewerStateServiceInterface;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\DirectEditing\IToken;
use OCP\IRequest;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;

#[CoversClass(HtmlDirectEditor::class)]
#[AllowMockObjectsWithoutExpectations]
class HtmlDirectEditorTest extends TestCase {

    protected DirectEditingServiceInterface&MockObject $directEditing;

    protected ViewerStateServiceInterface&MockObject $viewerState;

    protected IInitialState&MockObject $initialState;

    protected IRequest&Stub $request;

    protected AssetServiceInterface&MockObject $assets;

    protected HtmlDirectEditor $editor;

    protected function setUp(): void {
        $this->directEditing = $this->createMock(DirectEditingServiceInterface::class);
        $this->viewerState = $this->createMock(ViewerStateServiceInterface::class);
        $this->initialState = $this->createMock(IInitialState::class);
        $this->request = $this->createStub(IRequest::class);
        $this->assets = $this->createMock(AssetServiceInterface::class);

        $this->editor = new HtmlDirectEditor($this->directEditing, $this->viewerState, $this->initialState, $this->request, $this->assets);
    }

    public function testEditorDescription(): void {
        $this->assertSame('htmlviewer', $this->editor->getId());
        $this->assertSame('HTML Viewer', $this->editor->getName());
        $this->assertSame(['text/html'], $this->editor->getMimetypes());
        $this->assertSame([], $this->editor->getMimetypesOptional());
        $this->assertSame([], $this->editor->getCreators());
        $this->assertFalse($this->editor->isSecure());
    }

    public function testOpenRendersEditorPage(): void {
        $file = new DirectEditingFile('token-value', 'page.html', 12, true);
        $this->request->method('getParam')->willReturnMap([['token', null, 'token-value']]);
        $this->directEditing->expects($this->once())->method('openFile')->with('token-value')->willReturn($file);
        $this->viewerState->expects($this->once())->method('provideViewerState')->with('alice');
        $this->initialState->expects($this->once())->method('provideInitialState')->with('directEditing', $this->identicalTo($file));
        $this->assets->expects($this->once())->method('loadDirectEditingAssets');
        $this->assets->expects($this->never())->method('loadViewerAssets');

        $response = $this->editor->open($this->createToken());

        $this->assertInstanceOf(TemplateResponse::class, $response);
        $this->assertNotInstanceOf(NotFoundResponse::class, $response);
        $this->assertSame('htmlviewer', $response->getApp());
        $this->assertSame('directEditing', $response->getTemplateName());
        $this->assertSame(TemplateResponse::RENDER_AS_BASE, $response->getRenderAs());
        $this->assertSame([], $response->getParams());
    }

    public static function invalidTokenParameters(): array {
        return [
            'missing' => [null],
            'array'   => [['token-value']],
            'number'  => [42],
        ];
    }

    #[DataProvider('invalidTokenParameters')]
    public function testOpenRejectsInvalidTokenParameter(mixed $token): void {
        $this->request->method('getParam')->willReturn($token);
        $this->directEditing->expects($this->never())->method('openFile');
        $this->expectNoEditorPage();

        $this->assertInstanceOf(NotFoundResponse::class, $this->editor->open($this->createToken()));
    }

    public function testOpenRejectsInvalidToken(): void {
        $this->request->method('getParam')->willReturn('token-value');
        $this->directEditing->expects($this->once())->method('openFile')->willThrowException(new InvalidTokenException());
        $this->expectNoEditorPage();

        $this->assertInstanceOf(NotFoundResponse::class, $this->editor->open($this->createToken()));
    }

    protected function expectNoEditorPage(): void {
        $this->viewerState->expects($this->never())->method('provideViewerState');
        $this->initialState->expects($this->never())->method('provideInitialState');
        $this->assets->expects($this->never())->method($this->anything());
    }

    protected function createToken(): IToken {
        $token = $this->createStub(IToken::class);
        $token->method('getUser')->willReturn('alice');

        return $token;
    }
}
