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

namespace OCA\HtmlViewer\Dto;

use OCA\HtmlViewer\Exception\InvalidSettingValueException;
use OCA\HtmlViewer\Settings\AppSetting;
use OCA\HtmlViewer\Settings\AppSettingType;

/**
 * A validated new value for an app setting.
 *
 * Whitespace in the CSP and the sandbox is normalized. An empty string restores the default policy.
 */
final readonly class AppSettingValue {

    /**
     * Upper limit for the size settings, prevents an overflow when the megabytes are converted to bytes
     */
    public const MAX_MEGABYTES = 1024 * 1024;

    public const MAX_POLICY_LENGTH = 4096;

    /**
     * The CSP is used in an HTML attribute and in the CSP header of Nextcloud, so only printable ASCII is allowed
     */
    protected const CSP_PATTERN = '/^[\x20-\x7E]*$/';

    protected const SANDBOX_PATTERN = '/^(allow-[a-z-]+( allow-[a-z-]+)*)?$/';

    public bool|int|string $value;

    /**
     * @throws InvalidSettingValueException
     */
    public function __construct(
        public AppSetting $setting,
        mixed             $value,
    ) {
        $this->value = match ($setting->getType()) {
            AppSettingType::Bool   => $this->validateBool($value),
            AppSettingType::Int    => $this->validateMegabytes($value),
            AppSettingType::String => $this->validatePolicy($value),
        };
    }

    protected function validateBool(mixed $value): bool {
        if(!is_bool($value)) {
            throw new InvalidSettingValueException("{$this->setting->value} must be a boolean");
        }

        return $value;
    }

    protected function validateMegabytes(mixed $value): int {
        if(!is_int($value) || $value < 0 || $value > self::MAX_MEGABYTES) {
            throw new InvalidSettingValueException("{$this->setting->value} must be an integer between 0 and ".self::MAX_MEGABYTES);
        }

        return $value;
    }

    protected function validatePolicy(mixed $value): string {
        if(!is_string($value) || strlen($value) > self::MAX_POLICY_LENGTH) {
            throw new InvalidSettingValueException("{$this->setting->value} must be a string with up to ".self::MAX_POLICY_LENGTH.' characters');
        }

        $value   = trim(preg_replace('/\s+/', ' ', $value) ?? '');
        $pattern = $this->setting === AppSetting::Sandbox ? self::SANDBOX_PATTERN:self::CSP_PATTERN;
        if(preg_match($pattern, $value) !== 1) {
            throw new InvalidSettingValueException("{$this->setting->value} contains invalid characters");
        }

        return $value;
    }
}
