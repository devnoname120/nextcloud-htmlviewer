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
use OCP\AppFramework\Controller;
use OCP\AppFramework\Services\IAppConfig;
use OCP\IRequest;

class SettingsController extends Controller {
    public function __construct(
        protected            $userId,
        IRequest             $request,
        protected IAppConfig $config,
    ) {
        parent::__construct(Application::APP_ID, $request);
    }

    public function disableWarning() {
        $this->config->setUserValue($this->userId, 'disableWarning', 1);
    }
}