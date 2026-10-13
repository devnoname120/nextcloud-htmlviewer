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

/**
 * The app config settings which can be changed by administrators.
 * The value of a case is the key of the setting in the app config.
 */
enum AppSetting: string {
    case NewTabLinks = 'newTabLinks';
    case AllowJs = 'allowJs';
    case DirectEditing = 'directEditing';
    case MaxSize = 'maxSize';
    case MaxEditorSize = 'maxEditorSize';
    case MaxCodeEditorSize = 'maxCodeEditorSize';
    case Csp = 'csp';
    case Sandbox = 'sandbox';

    /**
     * The value used when the setting is not configured.
     * Null for the CSP and the sandbox means that the default policy for the JavaScript setting is used.
     */
    public function getDefault(): bool|int|null {
        return match ($this) {
            self::NewTabLinks, self::AllowJs => false,
            self::DirectEditing              => true,
            self::MaxSize                    => 32,
            self::MaxEditorSize              => 16,
            self::MaxCodeEditorSize          => 5,
            self::Csp, self::Sandbox         => null,
        };
    }

    public function getType(): AppSettingType {
        return match ($this) {
            self::NewTabLinks, self::AllowJs, self::DirectEditing     => AppSettingType::Bool,
            self::MaxSize, self::MaxEditorSize, self::MaxCodeEditorSize => AppSettingType::Int,
            self::Csp, self::Sandbox                                    => AppSettingType::String,
        };
    }
}
