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

namespace OCA\HtmlViewer\Dto;

/**
 * File size limits in bytes.
 * The editor limit never exceeds the viewer limit and the code editor limit never exceeds the editor limit.
 */
final readonly class FileSizeLimits {

    public function __construct(
        public int $maxSize,
        public int $maxEditorSize,
        public int $maxCodeEditorSize,
    ) {
    }
}