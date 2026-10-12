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

use OCA\HtmlViewer\Dto\DirectEditingFile;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DirectEditingFile::class)]
class DirectEditingFileTest extends TestCase {

    public function testJsonContainsOnlyPublicFileData(): void {
        $file = new DirectEditingFile('token-value', 'page.html', 12, true);

        $this->assertSame(
            '{"token":"token-value","name":"page.html","size":12,"editable":true}',
            json_encode($file, JSON_THROW_ON_ERROR)
        );
    }
}
