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
 * Send a message to the mobile app which opened the direct editing page.
 * Android provides the DirectEditingMobileInterface object, iOS a WebKit message handler.
 * Known messages are "loading", "loaded" and "close".
 *
 * @param {String} message
 */
export function callMobileInterface(message) {
    try {
        if(typeof window.DirectEditingMobileInterface?.[message] === 'function') {
            window.DirectEditingMobileInterface[message]();
        }

        window.webkit?.messageHandlers?.DirectEditingMobileInterface?.postMessage(message);
    } catch(e) {
        console.error('Could not send message to the mobile app', message, e);
    }

    window.postMessage(message, window.location.origin);
}
