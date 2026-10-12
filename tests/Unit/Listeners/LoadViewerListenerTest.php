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

use OCA\HtmlViewer\Listeners\LoadViewerListener;
use OCA\HtmlViewer\Service\AssetServiceInterface;
use OCA\HtmlViewer\Service\ViewerStateServiceInterface;
use OCA\Viewer\Event\LoadViewer;
use OCP\EventDispatcher\Event;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(LoadViewerListener::class)]
class LoadViewerListenerTest extends TestCase {

    public static function users(): array {
        return [
            'user'         => ['alice'],
            'public share' => [null],
        ];
    }

    #[DataProvider('users')]
    public function testLoadsAssetsAndViewerState(?string $userId): void {
        $assets = $this->createMock(AssetServiceInterface::class);
        $assets->expects($this->once())->method('loadViewerAssets');
        $assets->expects($this->never())->method('loadDirectEditingAssets');

        $viewerState = $this->createMock(ViewerStateServiceInterface::class);
        $viewerState->expects($this->once())->method('provideViewerState')->with($userId);

        (new LoadViewerListener($userId, $viewerState, $assets))->handle(new LoadViewer());
    }

    public function testIgnoresOtherEvents(): void {
        $assets = $this->createMock(AssetServiceInterface::class);
        $assets->expects($this->never())->method($this->anything());

        $viewerState = $this->createMock(ViewerStateServiceInterface::class);
        $viewerState->expects($this->never())->method('provideViewerState');

        (new LoadViewerListener('alice', $viewerState, $assets))->handle(new Event());
    }
}
