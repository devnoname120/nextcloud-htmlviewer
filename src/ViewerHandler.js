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
 * Props forwarded to the HTMLViewer component.
 * The Viewer app merges additional props (fileList, loaded, ...) into handler components, those are not forwarded.
 */
const FORWARDED_PROPS = ['size', 'source', 'permissions', 'showLoading', 'fileVersion', 'basename'];

/**
 * The Viewer app still runs on Vue 2 and renders handler components with its own Vue instance.
 * This plain Vue 2 component definition (no Vue import needed) acts as a bridge:
 * it renders an empty element and mounts the Vue 3 HTMLViewer app into it.
 */
export default {
    name : 'HtmlViewerHandler',
    props: {
        size       : Number,
        source     : String,
        permissions: String,
        showLoading: {
            type   : Boolean,
            default: false
        },
        fileVersion: {
            type   : Number,
            default: null
        },
        basename   : {
            type   : String,
            default: null
        }
    },
    render(h) {
        return h('div', {staticClass: 'htmlviewer-handler'});
    },
    created() {
        this.$watch(
            () => this.forwardedProps(),
            (props) => {
                if(this.htmlViewerApp) {
                    this.htmlViewerApp.update(props);
                }
            }
        );
    },
    mounted() {
        this.isUnmounted = false;
        this.htmlViewerApp = null;

        const isComparison = this.$parent.$el.querySelector('.viewer__content.viewer--split') !== null;
        if(isComparison) {
            let parentContainer = this.$parent.$el.querySelector('.viewer__content.viewer--split');
            parentContainer.style.display = 'flex';
            parentContainer.style.flexDirection = 'row-reverse';
        }

        import(/* webpackChunkName: "HTMLViewer" */'./viewer.js')
            .then(({mountViewer}) => {
                if(this.isUnmounted) {
                    return;
                }

                this.htmlViewerApp = mountViewer(
                    this.$el,
                    {...this.forwardedProps(), isComparison},
                    {
                        close : () => this.$parent.close(),
                        loaded: (value) => this.$emit('update:loaded', value)
                    }
                );
            })
            .catch(console.error);
    },
    beforeDestroy() {
        this.isUnmounted = true;
        if(this.htmlViewerApp) {
            this.htmlViewerApp.unmount();
            this.htmlViewerApp = null;
        }
    },
    methods: {
        forwardedProps() {
            const props = {};
            for(const key of FORWARDED_PROPS) {
                props[key] = this[key];
            }
            return props;
        }
    }
};
