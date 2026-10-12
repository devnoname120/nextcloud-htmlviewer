<!--
  - @copyright 2024 HtmlViewer
  -
  - @author Marius David Wieschollek
  - @license AGPL-3.0
  -
  - This file is part of the HtmlViewer App
  - created by Marius David Wieschollek.
  -->

<template>
    <div class="htmlviewer-container" :class="{'htmlviewer-has-editor': isEdit}">
        <ContentEditor :file="file" v-if="file.loaded && isEdit" v-on:close="isEdit=false;"/>
        <ContentViewer :file="file" :show-name="isComparison" :show-edit="showEditButton" v-on:edit="isEdit=true;" v-if="file.loaded"/>
        <ErrorMessage :file="file" v-if="file.isTooBig"/>
        <WarningDialog v-on:abort="abort" v-on:confirm="ok" v-if="hasDialog"/>
        <NcLoadingIcon class="htmlviewer-loader" :size="64" v-if="showLoading && !file.loaded"/>
    </div>
</template>

<script>
    import {defineAsyncComponent} from 'vue';
    import {loadState} from '@nextcloud/initial-state';
    import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon';
    import File from './models/File.js';
    import scriptWarning from "./utils/scriptWarning.js";
    import ErrorMessage from "./components/ErrorMessage.vue";
    import WarningDialog from "./components/WarningDialog.vue";
    import ContentViewer from "./components/ContentViewer.vue";

    export default {
        components: {
            ContentViewer,
            ContentEditor: defineAsyncComponent(() => import/* webpackChunkName: "ContentEditor" */("./components/ContentEditor.vue")),
            WarningDialog,
            ErrorMessage,
            NcLoadingIcon
        },
        props     : {
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
            },
            isComparison: {
                type   : Boolean,
                default: false
            }
        },
        emits     : ['update:loaded', 'close'],
        data() {
            let file = new File(this.basename, this.source, this.size, this.fileVersion);

            return {
                file,
                isEdit   : false,
                hasDialog: false
            };
        },
        mounted() {
            this.loadFile().catch(console.error);
        },
        computed: {
            showEditButton() {
                return !this.isComparison && !this.isEdit && this.file.loaded && this.permissions.indexOf('W') !== -1;
            }
        },
        methods : {
            async loadFile(force = false) {
                if(!force && !scriptWarning.status() && loadState('htmlviewer', 'allowJs')) {
                    this.hasDialog = true;
                    this.$emit('update:loaded', true);
                    return;
                }

                if(this.file.isTooBig) {
                    this.$emit('update:loaded', true);
                    return;
                }

                await this.file.loadContent();
                this.$emit('update:loaded', true);
            },
            abort() {
                this.hasDialog = false;
                this.$emit('close');
            },
            ok() {
                this.hasDialog = false;
                this.loadFile(true).catch(console.error);
            }
        },

        watch: {
            source(value) {
                this.file = new File(this.basename, value, this.size, this.fileVersion);
                this.isEdit = false;
                this.loadFile().catch(console.error);
            }
        }
    };
</script>

<style lang="scss">
.htmlviewer-handler {
    width  : 100% !important;
    height : 100%;
}
</style>

<style scoped lang="scss">
.htmlviewer-container {
    width  : 100% !important;
    height : 100%;

    &.htmlviewer-has-editor {
        display : flex;
        gap     : .25rem;

        .htmlviewer-content {
            border-radius : var(--border-radius-large);
            overflow      : hidden;
        }
    }

    .htmlviewer-loader {
        margin-top : 5rem;
    }
}
</style>