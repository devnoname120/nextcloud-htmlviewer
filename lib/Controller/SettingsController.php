<?php
/*
 * @copyright 2024 HtmlViewer
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the HtmlViewer App
 * created by Marius David Wieschollek.
 */

namespace OCA\HtmlViewer\Controller;

use OCA\HtmlViewer\AppInfo\Application;
use OCA\HtmlViewer\Dto\AppSettingValue;
use OCA\HtmlViewer\Exception\InvalidSettingValueException;
use OCA\HtmlViewer\Service\SettingsServiceInterface;
use OCA\HtmlViewer\Settings\AppSetting;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

class SettingsController extends Controller {
    public function __construct(
        protected                          $userId,
        IRequest                           $request,
        protected SettingsServiceInterface $settings,
    ) {
        parent::__construct(Application::APP_ID, $request);
    }

    #[NoAdminRequired]
    public function disableWarning(): DataResponse {
        $this->settings->disableScriptWarning($this->userId);

        return new DataResponse();
    }

    /**
     * Change an app setting, only available to administrators
     *
     * @param string $key   The app config key of the setting
     * @param mixed  $value The new value, an empty CSP or sandbox restores the default policy
     *
     * @return DataResponse The app settings after the change
     */
    public function updateAppSetting(string $key, mixed $value = null): DataResponse {
        $setting = AppSetting::tryFrom($key);
        if($setting === null) {
            return new DataResponse([], Http::STATUS_NOT_FOUND);
        }

        try {
            $this->settings->setAppSetting(new AppSettingValue($setting, $value));
        } catch(InvalidSettingValueException) {
            return new DataResponse([], Http::STATUS_BAD_REQUEST);
        }

        return new DataResponse($this->settings->getAppSettings());
    }

    /**
     * Restore the default value of an app setting, only available to administrators
     *
     * @param string $key The app config key of the setting
     *
     * @return DataResponse The app settings after the change
     */
    public function resetAppSetting(string $key): DataResponse {
        $setting = AppSetting::tryFrom($key);
        if($setting === null) {
            return new DataResponse([], Http::STATUS_NOT_FOUND);
        }

        $this->settings->resetAppSetting($setting);

        return new DataResponse($this->settings->getAppSettings());
    }
}
