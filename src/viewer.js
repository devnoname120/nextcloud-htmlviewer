/*
 * @copyright 2024 HtmlViewer
 *
 * @author Marius David Wieschollek
 * @license AGPL-3.0
 *
 * This file is part of the HtmlViewer App
 * created by Marius David Wieschollek.
 */

import {createApp, h, reactive} from 'vue';
import HTMLViewer from './HTMLViewer.vue';

/**
 * Mount the Vue 3 HTMLViewer into an element owned by the Vue 2 Viewer app
 *
 * @param {HTMLElement} element
 * @param {Object} props Props for the HTMLViewer component
 * @param {{close: Function, loaded: Function}} listeners
 * @return {{update: (function(Object): void), unmount: (function(): void)}}
 */
export function mountViewer(element, props, listeners) {
    const state = reactive({...props});

    const app = createApp(
        {
            render: () => h(
                HTMLViewer,
                {
                    ...state,
                    onClose          : listeners.close,
                    'onUpdate:loaded': listeners.loaded
                }
            )
        }
    );
    app.mount(element);

    return {
        update(props) {
            Object.assign(state, props);
        },
        unmount() {
            app.unmount();
        }
    };
}
