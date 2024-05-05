/*
 * @copyright 2024 HtmlViewer
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the HtmlViewer App
 * created by Marius David Wieschollek.
 */

import isPublicPage from './utils/isPublicPage.js';
import Vue from "vue";
import PublicPage from "./PublicPage.vue";
import {translate} from '@nextcloud/l10n';
import {generateFilePath} from "@nextcloud/router";

// eslint-disable-next-line
__webpack_public_path__ = generateFilePath(appName, '', 'js/');

window.addEventListener('DOMContentLoaded', function() {
    const mimetypeElmt = document.getElementById('mimetype'),
          isHtml       = mimetypeElmt && mimetypeElmt.value === 'text/html';

    mimetypeElmt.value = 'application/only-htmlviewer';
    if(!isPublicPage() || !isHtml) {
        return;
    }

    // Support displaying single DICOM file on public
    const previewContainer = document.getElementById('preview');
    if(previewContainer) {
        const div = document.createElement('div');
        div.id = 'htmlviewer-open';
        previewContainer.appendChild(div);

        Vue.mixin(
            {
                methods: {
                    t: (t, v) => { return translate('htmlviewer', t, v); }
                }
            }
        );

        new Vue(PublicPage);
    }
});