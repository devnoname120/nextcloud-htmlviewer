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
 * The type a setting is stored with in the app config
 */
enum AppSettingType {
    case Bool;
    case Int;
    case String;
}
