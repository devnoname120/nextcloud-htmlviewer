/*
 * @copyright 2026 HtmlViewer
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the HtmlViewer App
 * created by Marius David Wieschollek.
 */

import {createApp} from 'vue';
import {generateFilePath} from '@nextcloud/router';
import '@nextcloud/dialogs/style.css';
import AdminSettings from './AdminSettings.vue';

// eslint-disable-next-line
__webpack_public_path__ = generateFilePath(appName, '', 'js/');

createApp(AdminSettings).mount('#htmlviewer-admin-settings');
