/*
 * @copyright 2024 HtmlViewer
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the HtmlViewer App
 * created by Marius David Wieschollek.
 */

import {generateFilePath} from '@nextcloud/router';

// eslint-disable-next-line
__webpack_public_path__ = generateFilePath(appName, '', 'js/');

OCA.Viewer.registerHandler(
    {
        id        : 'htmlviewer',
        mimes     : [
            'text/html'
        ],
        component : () => import/* webpackChunkName: "HTMLViewer" */("./HTMLViewer.vue"),
        canCompare: true
    }
);