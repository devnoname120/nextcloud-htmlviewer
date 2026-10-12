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

use OCA\HtmlViewer\Dto\AppSettings;
use OCA\HtmlViewer\Dto\AppSettingValue;
use OCA\HtmlViewer\Dto\FileSizeLimits;
use OCA\HtmlViewer\Dto\IframePolicy;
use OCA\HtmlViewer\Settings\AppSetting;

/**
 * Reads and writes all app and user settings of the app
 */
interface SettingsServiceInterface {

    /**
     * The app settings as they are configured, see AppSettings
     */
    public function getAppSettings(): AppSettings;

    /**
     * The values the app settings have when they are not configured
     */
    public function getDefaultAppSettings(): AppSettings;

    /**
     * Store an app setting. An empty CSP or sandbox restores the default policy.
     */
    public function setAppSetting(AppSettingValue $value): void;

    /**
     * Delete an app setting, so the default value is used again
     */
    public function resetAppSetting(AppSetting $setting): void;

    public function getFileSizeLimits(): FileSizeLimits;

    /**
     * Whether the app offers itself as direct editor for HTML files to the mobile and desktop clients
     */
    public function isDirectEditingEnabled(): bool;

    /**
     * The configured CSP and sandbox of the iframe, or the default policy for the JavaScript setting.
     * Permissions needed for links in new tabs are not included.
     */
    public function getIframePolicy(): IframePolicy;

    /**
     * The policy which is used when no custom CSP or sandbox is configured
     */
    public function getDefaultIframePolicy(bool $allowJs): IframePolicy;

    /**
     * Whether the user disabled the warning shown before HTML files with JavaScript are opened
     */
    public function isScriptWarningDisabled(string $userId): bool;

    public function disableScriptWarning(string $userId): void;
}
