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
use OCP\AppFramework\Services\IAppConfig;
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

    public function __construct(protected IAppConfig $config,) {
    }

    public function handle(Event $event): void {
        if(!$event instanceof AddContentSecurityPolicyEvent) {
            return;
        }

        $csp = new EmptyContentSecurityPolicy();
        $csp->addAllowedFrameDomain('blob:');

        $customCsp = $this->config->getAppValueString('csp');
        if($this->config->getAppValueBool('allowJs') || str_contains($customCsp, 'eval')) {
            $csp->allowEvalScript();
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
                if(str_starts_with($rule, 'nonce-') || str_starts_with($rule, 'sha256-') || str_starts_with($rule, 'sha384-') || str_starts_with($rule, 'sha512-')) {
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
            $rules = explode(' ', trim($part));
            $type  = array_shift($rules);

            $cspRules[ $type ] = $rules;
        }

        return $cspRules;
    }
}