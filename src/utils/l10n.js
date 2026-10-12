/*
 * @copyright 2026 HtmlViewer
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the HtmlViewer App
 * created by Marius David Wieschollek.
 */

import {translate} from "@nextcloud/l10n";

/**
 * Translate a language key from translations/en.po.
 * Keys must be passed as string literals so the build can check that they exist.
 *
 * @param {string} key
 * @param {Object} [vars]
 * @return {string}
 */
export function t(key, vars) {
    return translate('htmlviewer', key, vars);
}
