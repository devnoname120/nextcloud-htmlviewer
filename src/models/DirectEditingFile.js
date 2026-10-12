/*
 * @copyright 2026 HtmlViewer
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the HtmlViewer App
 * created by Marius David Wieschollek.
 */

import axios from "@nextcloud/axios";
import {generateUrl} from "@nextcloud/router";
import File from "./File.js";

const TOKEN_HEADER = 'X-HtmlViewer-Token';

/**
 * A file opened through the direct editing API of the clients.
 * The editor page has no user session, so the file is loaded and saved with the direct editing token.
 */
export default class DirectEditingFile extends File {

    get editable() {
        return this._editable;
    }

    /**
     * @param {{token: String, name: String, size: Number, editable: Boolean}} data The initial state of the page
     */
    constructor(data) {
        super(data.name, null, data.size, null);
        this._token = data.token;
        this._editable = data.editable;
    }

    async _download() {
        const response = await axios.get(
            this._getUrl(),
            {
                headers          : {[TOKEN_HEADER]: this._token},
                responseType     : 'text',
                // The content is HTML and must not be parsed even if it happens to be valid JSON
                transformResponse: [(data) => data]
            }
        );

        return response.data;
    }

    async _upload(data) {
        await axios.put(this._getUrl(), {content: data}, {headers: {[TOKEN_HEADER]: this._token}});
    }

    _getUrl() {
        return generateUrl('/apps/htmlviewer/directEditing/content');
    }
}
