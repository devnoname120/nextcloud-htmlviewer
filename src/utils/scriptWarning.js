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
import {isPublicShare} from '@nextcloud/sharing/public';
import axios from "@nextcloud/axios";
import {generateUrl} from "@nextcloud/router";

export default new class ScriptWarning {

    constructor() {
        this._disableWarning = loadState('htmlviewer', 'disableWarning', false);
        // The direct editing page has no user session to save the setting with
        this._canSave = !isPublicShare() && loadState('htmlviewer', 'directEditing', null) === null;
    }

    status() {
        return this._disableWarning;
    }

    disable() {
        if(this._canSave && !this._disableWarning) {
            axios.post(generateUrl('/apps/htmlviewer/settings/warning'))
                 .catch(console.error);
        }
        this._disableWarning = true;
    }
};