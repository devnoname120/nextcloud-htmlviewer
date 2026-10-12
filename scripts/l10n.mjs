/*
 * @copyright 2026 HtmlViewer
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the HtmlViewer App
 * created by Marius David Wieschollek.
 */

/**
 * Manages the translations of the app.
 *
 * The gettext files in "translations/" are the master files. The messages use language keys as msgid,
 * "en.po" contains the English texts which are also used for missing translations in other languages.
 *
 * Usage:
 *   node scripts/l10n.mjs build  Validate the PO files and create the Nextcloud files in "l10n/" from them
 *   node scripts/l10n.mjs sync   Update all PO files with the keys from "en.po"
 */

import fs from 'node:fs';
import path from 'node:path';
import {fileURLToPath} from 'node:url';
import {po} from 'gettext-parser';

const APP_ID = 'htmlviewer';
const SOURCE_LANGUAGE = 'en';
const ROOT_DIR = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
export const TRANSLATIONS_DIR = path.join(ROOT_DIR, 'translations');
const OUTPUT_DIR = path.join(ROOT_DIR, 'l10n');
const SOURCE_DIR = path.join(ROOT_DIR, 'src');
// Matches the language keys used with the t() helper from src/utils/l10n.js
const KEY_USAGE_PATTERN = /\bt\(\s*(['"])([\w.-]+)\1/g;
const PLACEHOLDER_PATTERN = /{\w+}/g;

/**
 * @param {string} language
 * @return {string}
 */
function getPoFilePath(language) {
    return path.join(TRANSLATIONS_DIR, `${language}.po`);
}

/**
 * @return {string[]}
 */
function getLanguages() {
    return fs.readdirSync(TRANSLATIONS_DIR)
             .filter((file) => file.endsWith('.po'))
             .map((file) => path.basename(file, '.po'))
             .sort();
}

/**
 * @param {string} language
 * @return {Object}
 */
function readPoFile(language) {
    return po.parse(fs.readFileSync(getPoFilePath(language)));
}

/**
 * Returns all messages of the default context, without the PO header entry
 *
 * @param {Object} catalog
 * @return {Map<string, Object>}
 */
function getMessages(catalog) {
    let messages = new Map();
    for(let [key, message] of Object.entries(catalog.translations[''] ?? {})) {
        if(key !== '') {
            messages.set(key, message);
        }
    }

    return messages;
}

/**
 * Returns the key used by Nextcloud for a message
 *
 * @param {Object} message
 * @return {string}
 */
function getNextcloudKey(message) {
    if(message.msgid_plural) {
        return `_${message.msgid}_::_${message.msgid_plural}_`;
    }

    return message.msgid;
}

/**
 * Returns the translation of a message or the source text if it is not translated
 *
 * @param {Object} message
 * @param {Object} sourceMessage
 * @return {string|string[]}
 */
function getNextcloudValue(message, sourceMessage) {
    let isTranslated = message && !message.comments?.flag?.includes('fuzzy') && message.msgstr.every((text) => text !== '');
    let msgstr = isTranslated ? message.msgstr:sourceMessage.msgstr;

    return sourceMessage.msgid_plural ? msgstr:msgstr[0];
}

/**
 * @param {string} text
 * @return {string[]}
 */
function getPlaceholders(text) {
    return [...new Set(text.match(PLACEHOLDER_PATTERN) ?? [])].sort();
}

/**
 * @param {string} dir
 * @return {string[]}
 */
function findSourceFiles(dir) {
    return fs.readdirSync(dir, {withFileTypes: true}).flatMap((entry) => {
        let file = path.join(dir, entry.name);
        if(entry.isDirectory()) return findSourceFiles(file);

        return /\.(js|vue)$/.test(entry.name) ? [file]:[];
    });
}

/**
 * Checks that all keys used in the source code exist and that the translations use the same placeholders
 *
 * @param {Map<string, Object>} sourceMessages
 * @param {Map<string, Map<string, Object>>} translations
 * @return {{errors: string[], warnings: string[]}}
 */
function validate(sourceMessages, translations) {
    let errors = [], warnings = [];

    for(let file of findSourceFiles(SOURCE_DIR)) {
        let content = fs.readFileSync(file, 'utf8');
        for(let [, , key] of content.matchAll(KEY_USAGE_PATTERN)) {
            if(!sourceMessages.has(key)) {
                errors.push(`${path.relative(ROOT_DIR, file)}: Language key "${key}" does not exist in ${SOURCE_LANGUAGE}.po`);
            }
        }
    }

    for(let [key, message] of sourceMessages) {
        if(message.msgstr.some((text) => text === '')) {
            errors.push(`${SOURCE_LANGUAGE}.po: Language key "${key}" has no text`);
        }
    }

    for(let [language, messages] of translations) {
        for(let [key, message] of messages) {
            let sourceMessage = sourceMessages.get(key);
            if(!sourceMessage) {
                warnings.push(`${language}.po: Language key "${key}" does not exist in ${SOURCE_LANGUAGE}.po, run "npm run l10n:sync"`);
                continue;
            }

            let expected = getPlaceholders(sourceMessage.msgstr.join(' ')).join(', ');
            for(let text of message.msgstr) {
                if(text !== '' && getPlaceholders(text).join(', ') !== expected) {
                    errors.push(`${language}.po: Translation of "${key}" must use the placeholders [${expected}]`);
                }
            }
        }
    }

    return {errors, warnings};
}

/**
 * Creates the Nextcloud translation files from the PO files
 *
 * @return {{languages: string[], warnings: string[]}}
 */
export function build() {
    let sourceCatalog = readPoFile(SOURCE_LANGUAGE),
        sourceMessages = getMessages(sourceCatalog),
        catalogs = new Map(),
        translations = new Map();

    for(let language of getLanguages()) {
        let catalog = language === SOURCE_LANGUAGE ? sourceCatalog:readPoFile(language);
        catalogs.set(language, catalog);
        if(language !== SOURCE_LANGUAGE) translations.set(language, getMessages(catalog));
    }

    let {errors, warnings} = validate(sourceMessages, translations);
    if(errors.length !== 0) {
        throw new Error(`Invalid translations:\n${errors.join('\n')}`);
    }

    fs.mkdirSync(OUTPUT_DIR, {recursive: true});
    for(let file of fs.readdirSync(OUTPUT_DIR)) {
        if(/\.(js|json)$/.test(file)) fs.rmSync(path.join(OUTPUT_DIR, file));
    }

    for(let [language, catalog] of catalogs) {
        let messages = translations.get(language) ?? sourceMessages,
            strings = {};

        for(let [key, sourceMessage] of sourceMessages) {
            strings[getNextcloudKey(sourceMessage)] = getNextcloudValue(messages.get(key), sourceMessage);
        }

        let pluralForm = catalog.headers['Plural-Forms'] || sourceCatalog.headers['Plural-Forms'],
            json = JSON.stringify(strings, null, 4),
            outputFile = path.join(OUTPUT_DIR, language);

        fs.writeFileSync(`${outputFile}.json`, `${JSON.stringify({translations: strings, pluralForm}, null, 4)}\n`);
        fs.writeFileSync(`${outputFile}.js`, `OC.L10N.register(\n    ${JSON.stringify(APP_ID)},\n    ${json.replaceAll('\n', '\n    ')},\n    ${JSON.stringify(pluralForm)}\n);\n`);
    }

    return {languages: [...catalogs.keys()], warnings};
}

/**
 * Updates the PO files of all languages with the keys and texts from the source language
 *
 * @return {string[]}
 */
export function sync() {
    let sourceMessages = getMessages(readPoFile(SOURCE_LANGUAGE)),
        languages = getLanguages().filter((language) => language !== SOURCE_LANGUAGE);

    for(let language of languages) {
        let catalog = readPoFile(language),
            messages = getMessages(catalog),
            synced = {};

        for(let [key, sourceMessage] of sourceMessages) {
            let message = messages.get(key),
                pluralCount = sourceMessage.msgid_plural ? Math.max(sourceMessage.msgstr.length, message?.msgstr.length ?? 0):1,
                msgstr = Array.from({length: pluralCount}, (_, i) => message?.msgstr[i] ?? '');

            synced[key] = {
                msgid   : key,
                msgstr,
                comments: {
                    ...message?.comments,
                    // The extracted comment shows the English text to translators
                    extracted: sourceMessage.msgstr.join('\n')
                }
            };
            if(sourceMessage.msgid_plural) synced[key].msgid_plural = sourceMessage.msgid_plural;
        }

        catalog.translations = {'': synced};
        fs.writeFileSync(getPoFilePath(language), `${po.compile(catalog, {foldLength: 0, sort: false})}\n`);
    }

    return languages;
}

if(process.argv[1] && path.resolve(process.argv[1]) === fileURLToPath(import.meta.url)) {
    let command = process.argv[2] ?? 'build';
    try {
        if(command === 'build') {
            let {languages, warnings} = build();
            warnings.forEach((warning) => console.warn(`Warning: ${warning}`));
            console.log(`Created translations for: ${languages.join(', ')}`);
        } else if(command === 'sync') {
            console.log(`Updated PO files for: ${sync().join(', ')}`);
        } else {
            console.error(`Unknown command "${command}", use "build" or "sync"`);
            process.exitCode = 1;
        }
    } catch(e) {
        console.error(e.message);
        process.exitCode = 1;
    }
}
