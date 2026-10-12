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
use OCP\AppFramework\Services\IAppConfig;
use OCP\Exceptions\AppConfigTypeConflictException;

class SettingsService implements SettingsServiceInterface {

    protected const MEGABYTE = 1024 * 1024;

    protected const DEFAULT_CSP = "default-src blob: data: ; style-src 'unsafe-inline' blob: data:";

    protected const DEFAULT_SANDBOX = '';

    protected const DEFAULT_JS_CSP = "default-src 'unsafe-eval' 'unsafe-inline' 'wasm-unsafe-eval' blob: data:";

    protected const DEFAULT_JS_SANDBOX = 'allow-scripts allow-presentation allow-modals allow-downloads';

    protected const USER_DISABLE_WARNING = 'disableWarning';

    public function __construct(protected IAppConfig $config) {
    }

    public function getAppSettings(): AppSettings {
        return new AppSettings(
            $this->getBool(AppSetting::NewTabLinks),
            $this->getBool(AppSetting::AllowJs),
            $this->getBool(AppSetting::DirectEditing),
            $this->getInt(AppSetting::MaxSize),
            $this->getInt(AppSetting::MaxEditorSize),
            $this->getInt(AppSetting::MaxCodeEditorSize),
            $this->getString(AppSetting::Csp),
            $this->getString(AppSetting::Sandbox),
        );
    }

    public function getDefaultAppSettings(): AppSettings {
        return new AppSettings(
            AppSetting::NewTabLinks->getDefault(),
            AppSetting::AllowJs->getDefault(),
            AppSetting::DirectEditing->getDefault(),
            AppSetting::MaxSize->getDefault(),
            AppSetting::MaxEditorSize->getDefault(),
            AppSetting::MaxCodeEditorSize->getDefault(),
            AppSetting::Csp->getDefault(),
            AppSetting::Sandbox->getDefault(),
        );
    }

    public function setAppSetting(AppSettingValue $value): void {
        if($value->value === '') {
            $this->resetAppSetting($value->setting);

            return;
        }

        try {
            $this->writeAppValue($value);
        } catch(AppConfigTypeConflictException) {
            // Values set with occ without a type may be stored with a different type
            $this->resetAppSetting($value->setting);
            $this->writeAppValue($value);
        }
    }

    public function resetAppSetting(AppSetting $setting): void {
        $this->config->deleteAppValue($setting->value);
    }

    public function getFileSizeLimits(): FileSizeLimits {
        $maxSize = max(0, $this->getInt(AppSetting::MaxSize)) * self::MEGABYTE;

        $maxEditorSize = max(0, $this->getInt(AppSetting::MaxEditorSize)) * self::MEGABYTE;
        $maxEditorSize = min($maxEditorSize, $maxSize);

        $maxCodeEditorSize = max(0, $this->getInt(AppSetting::MaxCodeEditorSize)) * self::MEGABYTE;
        $maxCodeEditorSize = min($maxCodeEditorSize, $maxEditorSize);

        return new FileSizeLimits($maxSize, $maxEditorSize, $maxCodeEditorSize);
    }

    public function isDirectEditingEnabled(): bool {
        return $this->getBool(AppSetting::DirectEditing);
    }

    public function getIframePolicy(): IframePolicy {
        $default = $this->getDefaultIframePolicy($this->getBool(AppSetting::AllowJs));

        return new IframePolicy(
            $this->getString(AppSetting::Csp) ?? $default->csp,
            $this->getString(AppSetting::Sandbox) ?? $default->sandbox,
        );
    }

    public function getDefaultIframePolicy(bool $allowJs): IframePolicy {
        if($allowJs) {
            return new IframePolicy(self::DEFAULT_JS_CSP, self::DEFAULT_JS_SANDBOX);
        }

        return new IframePolicy(self::DEFAULT_CSP, self::DEFAULT_SANDBOX);
    }

    public function isScriptWarningDisabled(string $userId): bool {
        return intval($this->config->getUserValue($userId, self::USER_DISABLE_WARNING, '0')) === 1;
    }

    public function disableScriptWarning(string $userId): void {
        $this->config->setUserValue($userId, self::USER_DISABLE_WARNING, '1');
    }

    protected function getBool(AppSetting $setting): bool {
        $default = (bool) $setting->getDefault();
        try {
            return $this->config->getAppValueBool($setting->value, $default);
        } catch(AppConfigTypeConflictException) {
            return filter_var($this->getStoredValue($setting), FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $default;
        }
    }

    protected function getInt(AppSetting $setting): int {
        $default = (int) $setting->getDefault();
        try {
            return $this->config->getAppValueInt($setting->value, $default);
        } catch(AppConfigTypeConflictException) {
            $value = $this->getStoredValue($setting);

            return is_numeric($value) ? (int) $value:$default;
        }
    }

    /**
     * @return string|null The configured value or null if the setting is not configured
     */
    protected function getString(AppSetting $setting): ?string {
        if(!$this->config->hasAppKey($setting->value)) {
            return null;
        }

        try {
            return $this->config->getAppValueString($setting->value);
        } catch(AppConfigTypeConflictException) {
            $value = $this->getStoredValue($setting);

            return is_scalar($value) ? (string) $value:null;
        }
    }

    /**
     * Read a value regardless of its type, for values which were stored with another type, e.g. with occ
     */
    protected function getStoredValue(AppSetting $setting): mixed {
        return $this->config->getAllAppValues($setting->value)[ $setting->value ] ?? null;
    }

    protected function writeAppValue(AppSettingValue $value): void {
        $key = $value->setting->value;

        match (true) {
            is_bool($value->value) => $this->config->setAppValueBool($key, $value->value),
            is_int($value->value)  => $this->config->setAppValueInt($key, $value->value),
            default                => $this->config->setAppValueString($key, $value->value),
        };
    }
}
