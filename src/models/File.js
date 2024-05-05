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

    get raw() {
        return this._raw ?? this._content;
    }

    get loaded() {
        return this._content !== null;
    }

    get isTooBig() {
        return this.size > loadState('htmlviewer', 'maxSize');
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

        const response = await axios.get(this._source);
        this.setContent(response.data);
        this._saved = true;

        return this._content;
    }

    setContent(data) {
        if(loadState('htmlviewer', 'allowJs')) {
            this._raw = data;
            let nonce = loadState('htmlviewer', 'nonce');
            data = data.replace(/\<script/g, `<script nonce="${nonce}"`);
        } else {
            this._raw = null;
        }

        let blob = new Blob([data], {type: "text/html"});
        this._content = URL.createObjectURL(blob);
        this._saved = false;
    }

    async save() {
        const response = await axios.put(this._source, this.raw);

        if(response.status > 299) {
            throw new Error(response.statusText);
        }
        this._saved = true;
    }
}