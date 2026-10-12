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

interface ViewerStateServiceInterface {

    /**
     * Provide the settings needed by the viewer and editor frontend as initial state
     *
     * @param string|null $userId The user the files are shown to, null for public shares
     */
    public function provideViewerState(?string $userId): void;
}