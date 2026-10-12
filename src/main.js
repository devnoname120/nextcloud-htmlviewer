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
import ViewerHandler from './ViewerHandler.js';
import {claimViewerMimes} from './utils/handlerPriority.js';

// eslint-disable-next-line
__webpack_public_path__ = generateFilePath(appName, '', 'js/');

const handler = {
    id        : 'htmlviewer',
    mimes     : [
        'text/html'
    ],
    component : ViewerHandler,
    canCompare: true
};

OCA.Viewer.registerHandler(handler);
claimViewerMimes(OCA.Viewer, handler.id, handler.mimes);
