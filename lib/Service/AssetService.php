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

use OCA\HtmlViewer\AppInfo\Application;
use OCP\Util;

class AssetService implements AssetServiceInterface {

    public function loadViewerAssets(): void {
        Util::addScript(Application::APP_ID, 'htmlviewer-main', 'viewer');
        Util::addTranslations(Application::APP_ID);
    }

    public function loadDirectEditingAssets(): void {
        Util::addScript(Application::APP_ID, 'htmlviewer-directEditing');
        Util::addTranslations(Application::APP_ID);
    }

    public function loadAdminSettingsAssets(): void {
        Util::addScript(Application::APP_ID, 'htmlviewer-adminSettings');
        Util::addTranslations(Application::APP_ID);
    }
}
