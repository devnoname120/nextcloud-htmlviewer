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

/**
 * Adds the scripts and translations of the app to the page which is currently rendered
 */
interface AssetServiceInterface {

    /**
     * Load the scripts which register the app as handler in the Viewer app
     */
    public function loadViewerAssets(): void;

    /**
     * Load the scripts of the direct editing page
     */
    public function loadDirectEditingAssets(): void;

    /**
     * Load the scripts of the admin settings section
     */
    public function loadAdminSettingsAssets(): void;
}
