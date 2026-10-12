<!--
  - @copyright 2026 HtmlViewer
  -
  - @author Marius David Wieschollek
  - @license AGPL-3.0
  -
  - This file is part of the HtmlViewer App
  - created by Marius David Wieschollek.
  -->

<template>
    <div class="htmlviewer-direct-editing" :class="{'htmlviewer-has-editor': showEditor}">
        <ContentEditor :file="file" :dark-controls="false" v-on:close="close" v-if="showEditor"/>
        <ContentViewer :file="file" v-if="file.loaded"/>
        <ErrorMessage :file="file" v-if="file.isTooBig"/>
        <NcNoteCard class="htmlviewer-load-error" type="error" :text="loadErrorText" v-if="hasLoadError"/>
        <WarningDialog v-on:abort="close" v-on:confirm="confirmWarning" v-if="hasDialog"/>
        <NcLoadingIcon class="htmlviewer-loader" :size="64" v-if="isLoading"/>
        <div class="htmlviewer-close-container" v-if="!showEditor && !hasDialog && !isLoading">
            <NcButton variant="secondary" :aria-label="closeLabel" @click="close">
                <template #icon>
                    <NcIconSvgWrapper :path="mdiClose" :size="20"/>
                </template>
            </NcButton>
        </div>
    </div>
</template>

<script>
    import {defineAsyncComponent} from 'vue';
    import {loadState} from '@nextcloud/initial-state';
    import NcButton from '@nextcloud/vue/components/NcButton';
    import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper';
    import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon';
    import NcNoteCard from '@nextcloud/vue/components/NcNoteCard';
    import {mdiClose} from '@mdi/js';
    import DirectEditingFile from './models/DirectEditingFile.js';
    import scriptWarning from './utils/scriptWarning.js';
    import {callMobileInterface} from './utils/mobileInterface.js';
    import {t} from './utils/l10n.js';
    import ErrorMessage from './components/ErrorMessage.vue';
    import WarningDialog from './components/WarningDialog.vue';
    import ContentViewer from './components/ContentViewer.vue';

    /**
     * Page for the direct editing API, opened by the mobile and desktop clients without a user session
     */
    export default {
        components: {
            ContentViewer,
            ContentEditor: defineAsyncComponent(() => import/* webpackChunkName: "ContentEditor" */('./components/ContentEditor.vue')),
            WarningDialog,
            ErrorMessage,
            NcButton,
            NcIconSvgWrapper,
            NcLoadingIcon,
            NcNoteCard
        },
        data() {
            return {
                mdiClose,
                file        : new DirectEditingFile(loadState('htmlviewer', 'directEditing')),
                hasDialog   : false,
                hasLoadError: false,
                isLoading   : true
            };
        },
        beforeMount() {
            callMobileInterface('loading');
        },
        mounted() {
            this.loadFile().catch(console.error);
        },
        computed: {
            showEditor() {
                return this.file.loaded && this.file.editable;
            },
            closeLabel() {
                return t('directEditing.close');
            },
            loadErrorText() {
                return t('directEditing.load-error', {file: this.file.name});
            }
        },
        methods : {
            async loadFile(force = false) {
                try {
                    if(!force && !scriptWarning.status() && loadState('htmlviewer', 'allowJs')) {
                        this.hasDialog = true;
                    } else if(!this.file.isTooBig) {
                        await this.file.loadContent();
                    }
                } catch(e) {
                    console.error('Could not load file', this.file, e);
                    this.hasLoadError = true;
                } finally {
                    this.isLoading = false;
                    callMobileInterface('loaded');
                }
            },
            confirmWarning() {
                this.hasDialog = false;
                this.isLoading = true;
                this.loadFile(true).catch(console.error);
            },
            close() {
                this.hasDialog = false;
                callMobileInterface('close');
            }
        }
    };
</script>

<style lang="scss">
#htmlviewer-direct-editing {
    position   : fixed;
    inset      : 0;
    background : var(--color-main-background);
}
</style>

<style scoped lang="scss">
.htmlviewer-direct-editing {
    width    : 100%;
    height   : 100%;
    position : relative;

    &.htmlviewer-has-editor {
        display : flex;
        gap     : .25rem;
    }

    .htmlviewer-loader {
        margin-top : 5rem;
    }

    .htmlviewer-load-error {
        margin : 1rem;
    }

    .htmlviewer-close-container {
        position : absolute;
        top      : .5rem;
        right    : .5rem;
    }

    // The mobile apps are too narrow for the editor and preview side by side
    @media (max-width : 768px) {
        &.htmlviewer-has-editor {
            flex-direction : column;

            :deep(.htmlviewer-editor) {
                width  : 100%;
                height : 50%;
            }

            :deep(.htmlviewer-content) {
                height     : auto;
                flex-grow  : 1;
                min-height : 0;
            }
        }
    }
}
</style>
