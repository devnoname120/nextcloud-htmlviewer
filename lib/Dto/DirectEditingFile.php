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
 * The file opened through a direct editing token, as it is exposed to the editor page.
 * The file is only identified by the token, the internal file id is never exposed.
 */
final readonly class DirectEditingFile implements JsonSerializable {

    public function __construct(
        public string $token,
        public string $name,
        public int    $size,
        public bool   $editable,
    ) {
    }

    public function jsonSerialize(): array {
        return [
            'token'    => $this->token,
            'name'     => $this->name,
            'size'     => $this->size,
            'editable' => $this->editable,
        ];
    }
}