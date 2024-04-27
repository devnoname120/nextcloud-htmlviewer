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

namespace OCA\HtmlViewer\Listeners;

use OCP\AppFramework\Http\EmptyContentSecurityPolicy;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Security\CSP\AddContentSecurityPolicyEvent;

class CSPListener implements IEventListener {
    public function handle(Event $event): void {
        if(!$event instanceof AddContentSecurityPolicyEvent) {
            return;
        }

        $csp = new EmptyContentSecurityPolicy();
        $csp->addAllowedFrameDomain('blob:');
        $event->addPolicy($csp);
    }
}