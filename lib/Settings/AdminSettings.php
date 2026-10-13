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

namespace OCA\HtmlViewer\Settings;

use OCA\HtmlViewer\AppInfo\Application;
use OCA\HtmlViewer\Service\AssetServiceInterface;
use OCA\HtmlViewer\Service\SettingsServiceInterface;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\Settings\ISettings;

/**
 * Section of the app in the "Additional settings" of the administration settings
 */
class AdminSettings implements ISettings {

    public const SECTION = 'additional';

    public const PRIORITY = 50;

    public function __construct(
        protected SettingsServiceInterface $settings,
        protected IInitialState            $initialState,
        protected AssetServiceInterface    $assets,
    ) {
    }

    public function getForm(): TemplateResponse {
        $this->initialState->provideInitialState('appSettings', $this->settings->getAppSettings());
        $this->initialState->provideInitialState('defaultAppSettings', $this->settings->getDefaultAppSettings());
        $this->initialState->provideInitialState('defaultIframePolicy', $this->settings->getDefaultIframePolicy(false));
        $this->initialState->provideInitialState('defaultJsIframePolicy', $this->settings->getDefaultIframePolicy(true));

        $this->assets->loadAdminSettingsAssets();

        return new TemplateResponse(Application::APP_ID, 'adminSettings');
    }

    public function getSection(): string {
        return self::SECTION;
    }

    public function getPriority(): int {
        return self::PRIORITY;
    }
}
