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

    get loaded() {
        return this._content !== null;
    }

    get isTooBig() {
        return this.size > loadState('htmlviewer', 'maxSize');
    }

    constructor(name, source, size, version) {
        this._name = name;
        this._source = source;
        this._size = size;
        this._version = version;
        this._content = null;
    }

    async loadContent() {
        if(this.isLoaded || this.isTooBig) {
            return this._content;
        }

        const response = await axios.get(this._source);
        let content = response.data;

        if(loadState('htmlviewer', 'allowJs')) {
            let nonce = loadState('htmlviewer', 'nonce');
            content = response.data.replace(/\<script/g, `<script nonce="${nonce}"`);
        }

        let blob = new Blob([content], {type: "text/html"});
        this._content = URL.createObjectURL(blob);

        return this._content;
    }
}