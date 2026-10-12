/*
 * @copyright 2024 HtmlViewer
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the HtmlViewer App
 * created by Marius David Wieschollek.
 */

import axios from "@nextcloud/axios";
import {loadState} from "@nextcloud/initial-state";

/**
 *
 * @param {String} data
 * @return {String}
 */
function modifyLinks(data) {
    if(!loadState('htmlviewer', 'newTabLinks')) {
        return data;
    }

    const targetRegex   = /target=['"]?_blank/,
          protocolRegex = /href=['"]?https?:\/\//;

    return data.replace(/<a [^>]+/g, (link) => {
        if(targetRegex.test(link) || !protocolRegex.test(link)) {
            return link;
        }

        link += ' target="_blank"';
        if(link.indexOf('rel=') === -1) {
            link += ' rel="noreferrer noopener"';
        }
        return link;
    });
}

/**
 *
 * @param {String} data
 * @return {String}
 */
function enableJS(data) {
    if(loadState('htmlviewer', 'allowJs')) {
        let nonce = loadState('htmlviewer', 'nonce');
        data = data.replace(/<script/g, `<script nonce="${nonce}"`);
    }
    return data;
}

export default class File {

    get name() {
        return this._name;
    }

    set name(value) {
        this._name = value;
    }

    get source() {
        return this._source;
    }

    set source(value) {
        this._source = value;
    }

    get size() {
        return this._size;
    }

    set size(value) {
        this._size = value;
    }

    get version() {
        return this._version;
    }

    set version(value) {
        this._version = value;
    }

    get content() {
        return this._content;
    }

    get raw() {
        return this._raw;
    }

    get loaded() {
        return this._content !== null;
    }

    get isTooBig() {
        return this.size > loadState('htmlviewer', 'maxSize');
    }

    get isTooBigForEditor() {
        return this.size > loadState('htmlviewer', 'maxEditorSize');
    }

    get isTooBigForCodeEditor() {
        return this.size > loadState('htmlviewer', 'maxCodeEditorSize');
    }

    get isSaved() {
        return this._saved;
    }

    constructor(name, source, size, version) {
        this._name = name;
        this._source = source;
        this._size = size;
        this._version = version;
        this._content = null;
        this._raw = null;
        this._saved = true;
    }

    async loadContent(force = false) {
        if((!force && this._content !== null) || this.isTooBig) {
            return this._content;
        }

        this.setRaw(await this._download());
        this.updatePreview();
        this._saved = true;

        return this._content;
    }

    /**
     * Set the unprocessed html source without updating the preview
     *
     * @param {String} data
     */
    setRaw(data) {
        this._raw = data;
        this._saved = false;
    }

    /**
     * Process the current html source and create a new preview url
     */
    updatePreview() {
        let data = enableJS(this._raw);
        data = modifyLinks(data);

        if(this._content !== null) {
            URL.revokeObjectURL(this._content);
        }

        let blob = new Blob([data], {type: "text/html"});
        this._content = URL.createObjectURL(blob);
    }

    async save() {
        await this._upload(this.raw);
        this._saved = true;
    }

    /**
     * Fetch the html source of the file
     *
     * @return {Promise<String>}
     * @protected
     */
    async _download() {
        const response = await axios.get(this._source);

        return response.data;
    }

    /**
     * Store the html source of the file
     *
     * @param {String} data
     * @return {Promise<void>}
     * @protected
     */
    async _upload(data) {
        const response = await axios.put(this._source, data);

        if(response.status > 299) {
            throw new Error(response.statusText);
        }
    }
}
