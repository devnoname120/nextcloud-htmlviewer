
/*
 * @copyright 2024 HtmlViewer
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the HtmlViewer App
 * created by Marius David Wieschollek.
 */

const webpackConfig = require('@nextcloud/webpack-vue-config');
const path = require('path');

webpackConfig.entry.public = path.resolve(path.join('src', 'public.js'));

module.exports = webpackConfig;
