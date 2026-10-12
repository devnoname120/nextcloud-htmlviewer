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
use OCP\AppFramework\Services\IAppConfig;

class SettingsService implements SettingsServiceInterface {

    protected const MEGABYTE = 1024 * 1024;

    public function __construct(protected IAppConfig $config) {
    }

    public function getFileSizeLimits(): FileSizeLimits {
        $maxSize = max(0, $this->config->getAppValueInt('maxSize', 32)) * self::MEGABYTE;

        $maxEditorSize = max(0, $this->config->getAppValueInt('maxEditorSize', 16)) * self::MEGABYTE;
        $maxEditorSize = min($maxEditorSize, $maxSize);

        $maxCodeEditorSize = max(0, $this->config->getAppValueInt('maxCodeEditorSize', 5)) * self::MEGABYTE;
        $maxCodeEditorSize = min($maxCodeEditorSize, $maxEditorSize);

        return new FileSizeLimits($maxSize, $maxEditorSize, $maxCodeEditorSize);
    }

    public function isDirectEditingEnabled(): bool {
        return $this->config->getAppValueBool('directEditing', true);
    }
}