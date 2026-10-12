/*
 * @copyright 2024 HtmlViewer
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the HtmlViewer App
 * created by Marius David Wieschollek.
 */

/**
 * Make sure the handler with the given id is used for the given mimes, regardless of the order the apps were loaded.
 *
 * The Viewer app registers the mimes of all handlers on DOMContentLoaded in the order of availableHandlers.
 * The first handler wins and every later handler with the same mime causes an error in the log.
 * Other apps like Text also register text/html, so the handler is moved to the front and the mimes are removed
 * from the other handlers. Since other apps may register their handlers after this app, this is repeated after
 * every registration.
 *
 * @param {Object} viewer The OCA.Viewer instance
 * @param {String} handlerId
 * @param {String[]} mimes
 */
export function claimViewerMimes(viewer, handlerId, mimes) {
    const prioritize = () => {
        const handlers = viewer.availableHandlers;
        if(!Array.isArray(handlers)) {
            return;
        }

        const index = handlers.findIndex((handler) => handler?.id === handlerId);
        if(index === -1) {
            return;
        }
        if(index > 0) {
            handlers.unshift(...handlers.splice(index, 1));
        }

        for(const handler of handlers) {
            if(handler?.id === handlerId || !Array.isArray(handler?.mimes)) {
                continue;
            }

            // A handler without mimes is rejected by the Viewer, it loses against this one anyway
            const remainingMimes = handler.mimes.filter((mime) => !mimes.includes(mime));
            if(remainingMimes.length !== handler.mimes.length && remainingMimes.length !== 0) {
                handler.mimes = remainingMimes;
            }
        }
    };

    const registerHandler = viewer.registerHandler;
    viewer.registerHandler = (...args) => {
        const result = registerHandler.apply(viewer, args);
        prioritize();
        return result;
    };

    prioritize();
}
