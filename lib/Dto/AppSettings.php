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

use JsonSerializable;

/**
 * The app settings as they are configured, or their defaults if they are not configured.
 * The size limits are in megabytes and not adjusted to each other, see FileSizeLimits for the effective limits.
 * The CSP and the sandbox are null if the default policy is used.
 */
final readonly class AppSettings implements JsonSerializable {

    public function __construct(
        public bool    $newTabLinks,
        public bool    $allowJs,
        public bool    $directEditing,
        public int     $maxSize,
        public int     $maxEditorSize,
        public int     $maxCodeEditorSize,
        public ?string $csp,
        public ?string $sandbox,
    ) {
    }

    public function jsonSerialize(): array {
        return [
            'newTabLinks'       => $this->newTabLinks,
            'allowJs'           => $this->allowJs,
            'directEditing'     => $this->directEditing,
            'maxSize'           => $this->maxSize,
            'maxEditorSize'     => $this->maxEditorSize,
            'maxCodeEditorSize' => $this->maxCodeEditorSize,
            'csp'               => $this->csp,
            'sandbox'           => $this->sandbox,
        ];
    }
}
