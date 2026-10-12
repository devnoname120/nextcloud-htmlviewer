/*
 * @copyright 2024 HtmlViewer
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the HtmlViewer App
 * created by Marius David Wieschollek.
 */

import {getBuilder} from '@nextcloud/browser-storage';

const LINE_WRAPPING_KEY = 'editor.lineWrapping';

/**
 * Editor preferences of the current browser.
 * The browser storage may be unavailable (e.g. blocked site data), the defaults are used then.
 */
export default new class EditorSettings {

    constructor() {
        try {
            this._storage = getBuilder('htmlviewer').persist().build();
        } catch(e) {
            this._storage = null;
        }
    }

    get lineWrapping() {
        return this._getItem(LINE_WRAPPING_KEY) !== 'false';
    }

    set lineWrapping(value) {
        this._setItem(LINE_WRAPPING_KEY, value ? 'true' : 'false');
    }

    _getItem(key) {
        try {
            return this._storage?.getItem(key) ?? null;
        } catch(e) {
            return null;
        }
    }

    _setItem(key, value) {
        try {
            this._storage?.setItem(key, value);
        } catch(e) {
            console.warn('Could not save editor setting', key, e);
        }
    }
};
