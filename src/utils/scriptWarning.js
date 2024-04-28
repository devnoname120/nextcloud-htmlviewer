/*
 * @copyright 2024 HtmlViewer
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the HtmlViewer App
 * created by Marius David Wieschollek.
 */

import {loadState} from "@nextcloud/initial-state";
import isPublicPage from './isPublicPage.js';
import axios from "@nextcloud/axios";
import {generateUrl, getBaseUrl} from "@nextcloud/router";

export default new class ScriptWarning {

    constructor() {
        this._disableWarning = loadState('htmlviewer', 'disableWarning');
    }

    status() {
        return this._disableWarning;
    }

    disable() {
        if(!isPublicPage() && !this._disableWarning) {
            axios.get(
                getBaseUrl() +
                generateUrl('/apps/htmlviewer/settings/warning')
            );
        }
        this._disableWarning = true;
    }
};