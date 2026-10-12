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

use OCA\HtmlViewer\Service\SettingsServiceInterface;
use OCP\AppFramework\Http\EmptyContentSecurityPolicy;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\Security\CSP\AddContentSecurityPolicyEvent;

class CSPListener implements IEventListener {

    protected const CSP_RULE_MAPPING
        = [
            'script-src'  => 'addAllowedScriptDomain',
            'style-src'   => 'addAllowedStyleDomain',
            'font-src'    => 'addAllowedFontDomain',
            'img-src'     => 'addAllowedImageDomain',
            'connect-src' => 'addAllowedConnectDomain',
            'media-src'   => 'addAllowedMediaDomain',
            'object-src'  => 'addAllowedObjectDomain',
            'frame-src'   => 'addAllowedFrameDomain',
            'worker-src'  => 'addAllowedWorkerSrcDomain',
            'form-action' => 'addAllowedFormActionDomain'
        ];

    protected const CSP_IGNORED_RULES
        = [
            "'self'",
            "'unsafe-eval'",
            "'wasm-unsafe-eval'",
            "'unsafe-hashes'",
            "'unsafe-inline'",
            "'strict-dynamic'",
            "'report-sample'",
            "'inline-speculation-rules'",
            "'none'",
            'blob:',
            'data:',
            'mediastream:',
            'filesystem:'
        ];

    /**
     * Nonces and hashes only apply to the page they were created for, so they are never added to the CSP of Nextcloud
     */
    protected const CSP_NONCE_OR_HASH_PATTERN = "/^'?(nonce|sha256|sha384|sha512)-/";

    protected const CSP_EVAL_PATTERN = "/'(wasm-)?unsafe-eval'/";

    public function __construct(protected SettingsServiceInterface $settings) {
    }

    /**
     * @param Event $event
     *
     * @return void
     */
    public function handle(Event $event): void {
        if(!$event instanceof AddContentSecurityPolicyEvent) {
            return;
        }

        $csp = new EmptyContentSecurityPolicy();
        $csp->addAllowedFrameDomain('blob:');
        $csp->addAllowedMediaDomain('data:');

        $settings  = $this->settings->getAppSettings();
        $customCsp = $settings->csp ?? '';
        if($settings->allowJs || preg_match(self::CSP_EVAL_PATTERN, $customCsp) === 1) {
            $csp->allowEvalWasm();
        }

        if(!empty($customCsp)) {
            $csp = $this->applyCustomCSP($csp, $customCsp);
        }

        $event->addPolicy($csp);
    }

    /**
     * @param EmptyContentSecurityPolicy $csp
     * @param string                     $customCsp
     *
     * @return EmptyContentSecurityPolicy
     */
    protected function applyCustomCSP(EmptyContentSecurityPolicy $csp, string $customCsp): EmptyContentSecurityPolicy {
        $customCspRules = $this->getCustomCspRules($customCsp);

        foreach($customCspRules as $type => $rules) {
            if(!isset(self::CSP_RULE_MAPPING[ $type ])) {
                continue;
            }

            $method = self::CSP_RULE_MAPPING[ $type ];
            foreach($rules as $rule) {
                if(in_array($rule, self::CSP_IGNORED_RULES)) {
                    continue;
                }
                if(preg_match(self::CSP_NONCE_OR_HASH_PATTERN, $rule) === 1) {
                    continue;
                }

                $csp->{$method}($rule);
            }
        }

        return $csp;
    }

    /**
     * @param string $customCsp
     *
     * @return array
     */
    protected function getCustomCspRules(string $customCsp): array {
        $parts    = explode(';', $customCsp);
        $cspRules = [];

        foreach($parts as $part) {
            $rules = preg_split('/\s+/', trim($part), -1, PREG_SPLIT_NO_EMPTY);
            $type  = array_shift($rules);
            if($type === null) {
                continue;
            }

            $cspRules[ $type ] = $rules;
        }

        return $cspRules;
    }
}