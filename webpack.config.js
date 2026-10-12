/*
 * @copyright 2024 HtmlViewer
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the HtmlViewer App
 * created by Marius David Wieschollek.
 */

const path = require('path');
const webpackConfig = require('@nextcloud/webpack-vue-config');

/**
 * Creates the Nextcloud translation files in l10n/ from the PO files in translations/
 */
class TranslationsPlugin {
    apply(compiler) {
        let translationsDir = path.resolve(__dirname, 'translations'),
            build = async () => {
                let {build} = await import('./scripts/l10n.mjs');
                build().warnings.forEach((warning) => console.warn(`Warning: ${warning}`));
            };

        compiler.hooks.beforeRun.tapPromise('TranslationsPlugin', build);
        compiler.hooks.watchRun.tapPromise('TranslationsPlugin', build);
        compiler.hooks.afterCompile.tap('TranslationsPlugin', (compilation) => {
            compilation.contextDependencies.add(translationsDir);
        });
    }
}

webpackConfig.entry.directEditing = path.resolve(__dirname, 'src', 'directEditing.js');
webpackConfig.entry.adminSettings = path.resolve(__dirname, 'src', 'adminSettings.js');
webpackConfig.plugins.push(new TranslationsPlugin());

module.exports = webpackConfig;
