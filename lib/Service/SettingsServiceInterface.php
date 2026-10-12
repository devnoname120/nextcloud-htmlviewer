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

namespace OCA\HtmlViewer\Service;

use OCA\HtmlViewer\Dto\FileSizeLimits;

interface SettingsServiceInterface {

    public function getFileSizeLimits(): FileSizeLimits;

    /**
     * Whether the app offers itself as direct editor for HTML files to the mobile and desktop clients
     */
    public function isDirectEditingEnabled(): bool;
}