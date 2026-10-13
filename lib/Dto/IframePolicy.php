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
 * The CSP and sandbox attributes of the iframe which shows the HTML files
 */
final readonly class IframePolicy implements JsonSerializable {

    public function __construct(
        public string $csp,
        public string $sandbox,
    ) {
    }

    public function jsonSerialize(): array {
        return [
            'csp'     => $this->csp,
            'sandbox' => $this->sandbox,
        ];
    }
}
