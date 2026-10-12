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

declare(strict_types=1);

namespace OCA\HtmlViewer\AppInfo;

use OCA\HtmlViewer\Listeners\CSPListener;
use OCA\HtmlViewer\Listeners\LoadViewerListener;
use OCA\HtmlViewer\Listeners\RegisterDirectEditorListener;
use OCA\HtmlViewer\Service\AssetService;
use OCA\HtmlViewer\Service\AssetServiceInterface;
use OCA\HtmlViewer\Service\DirectEditingService;
use OCA\HtmlViewer\Service\DirectEditingServiceInterface;
use OCA\HtmlViewer\Service\SettingsService;
use OCA\HtmlViewer\Service\SettingsServiceInterface;
use OCA\HtmlViewer\Service\ViewerStateService;
use OCA\HtmlViewer\Service\ViewerStateServiceInterface;
use OCA\Viewer\Event\LoadViewer;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\DirectEditing\RegisterDirectEditorEvent;
use OCP\Security\CSP\AddContentSecurityPolicyEvent;

class Application extends App implements IBootstrap {
    public const APP_ID = 'htmlviewer';

    /**
     * The Text app also registers text/html for direct editing with the default priority.
     * The server and the clients use the first editor which supports a mimetype, so this editor is registered first.
     */
    public const DIRECT_EDITOR_PRIORITY = 10;

    public function __construct() {
        parent::__construct(self::APP_ID);
    }

    public function register(IRegistrationContext $context): void {
        $context->registerEventListener(LoadViewer::class, LoadViewerListener::class);
        $context->registerEventListener(AddContentSecurityPolicyEvent::class, CSPListener::class);
        $context->registerEventListener(RegisterDirectEditorEvent::class, RegisterDirectEditorListener::class, self::DIRECT_EDITOR_PRIORITY);

        $context->registerServiceAlias(SettingsServiceInterface::class, SettingsService::class);
        $context->registerServiceAlias(ViewerStateServiceInterface::class, ViewerStateService::class);
        $context->registerServiceAlias(DirectEditingServiceInterface::class, DirectEditingService::class);
        $context->registerServiceAlias(AssetServiceInterface::class, AssetService::class);
    }

    public function boot(IBootContext $context): void {
    }
}
