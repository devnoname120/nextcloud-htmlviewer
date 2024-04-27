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
use OCA\HtmlViewer\Listeners\LoadPublicViewerListener;
use OCA\HtmlViewer\Listeners\LoadViewerListener;
use OCA\Viewer\Event\LoadViewer;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\AppFramework\Http\Events\BeforeTemplateRenderedEvent;
use OCP\Security\CSP\AddContentSecurityPolicyEvent;

class Application extends App implements IBootstrap {
    public const APP_ID = 'htmlviewer';

    public function __construct() {
        parent::__construct(self::APP_ID);
    }

    public function register(IRegistrationContext $context): void {
        $context->registerEventListener(LoadViewer::class, LoadViewerListener::class);
        $context->registerEventListener(BeforeTemplateRenderedEvent::class, LoadPublicViewerListener::class);
        $context->registerEventListener(AddContentSecurityPolicyEvent::class, CSPListener::class);
    }

    public function boot(IBootContext $context): void {
    }
}
