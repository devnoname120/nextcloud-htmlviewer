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
    <div class="htmlviewer-editor">
        <!-- The controls are shown on the dark backdrop of the viewer, like the viewer header which also uses the dark theme -->
        <div class="htmlviewer-editor-controls" data-theme-dark="true">
            <template v-if="!file.isTooBigForEditor">
                <NcButton variant="tertiary" :aria-label="lineWrappingLabel" v-model:pressed="lineWrapping" v-if="!file.isTooBigForCodeEditor">
                    <template #icon>
                        <NcIconSvgWrapper :path="lineWrapping ? mdiWrap : mdiWrapDisabled" :size="20"/>
                    </template>
                </NcButton>
                <NcButton variant="tertiary" :aria-label="undoLabel" @click="undo" :disabled="file.isSaved || saveState === 'saving'">
                    <template #icon>
                        <NcIconSvgWrapper :path="mdiArrowULeftTop" :size="20"/>
                    </template>
                </NcButton>
                <NcButton variant="tertiary" :aria-label="saveStateLabel" @click="save">
                    <template #icon>
                        <NcSavingIndicatorIcon :name="saveStateLabel" :error="saveState === 'error'" :saving="saveState === 'saving'"/>
                    </template>
                </NcButton>
            </template>
            <NcButton variant="tertiary" :aria-label="closeLabel" @click="close">
                <template #icon>
                    <NcIconSvgWrapper :path="mdiClose" :size="20"/>
                </template>
            </NcButton>
        </div>
        <NcNoteCard v-if="file.isTooBigForEditor" type="error" :text="tooBigForEditorText"/>
        <template v-else-if="file.isTooBigForCodeEditor">
            <NcNoteCard type="warning" :text="tooBigForCodeEditorText"/>
            <textarea v-model="model" :disabled="saveState === 'saving'"></textarea>
        </template>
        <CodeEditor v-else v-model="model" :disabled="saveState === 'saving'" :line-wrapping="lineWrapping"/>
    </div>
</template>

<script>
    import {defineAsyncComponent} from 'vue';
    import File from "../models/File.js";
    import {formatFileSize} from "@nextcloud/files";
    import {loadState} from "@nextcloud/initial-state";
    import {t} from '../utils/l10n.js';
    import NcSavingIndicatorIcon from '@nextcloud/vue/components/NcSavingIndicatorIcon';
    import NcButton from "@nextcloud/vue/components/NcButton";
    import NcIconSvgWrapper from "@nextcloud/vue/components/NcIconSvgWrapper";
    import NcNoteCard from "@nextcloud/vue/components/NcNoteCard";
    import {mdiArrowULeftTop, mdiClose, mdiWrap, mdiWrapDisabled} from '@mdi/js';
    import editorSettings from "../utils/editorSettings.js";

    /**
     * The preview is updated at most once within this interval while the user is typing
     */
    const PREVIEW_INTERVAL = 5000;

    /**
     * Changes are saved automatically when there was no change for this time
     */
    const AUTOSAVE_DELAY = 5000;

    export default {
        components: {
            NcButton,
            NcSavingIndicatorIcon,
            NcIconSvgWrapper,
            NcNoteCard,
            CodeEditor: defineAsyncComponent(() => import/* webpackChunkName: "CodeEditor" */("./CodeEditor.vue"))
        },
        props     : {
            file    : File,
            showName: Boolean
        },
        emits     : ['close'],
        data() {
            return {
                mdiArrowULeftTop,
                mdiClose,
                mdiWrap,
                mdiWrapDisabled,
                lineWrapping  : editorSettings.lineWrapping,
                model         : this.file.raw,
                previewTimeout: null,
                previewPending: false,
                saveTimeout   : null,
                saveState     : 'saved'
            };
        },
        beforeUnmount() {
            this.flushPreview();
            if(!this.file.isSaved) {
                this.save().catch(console.error);
            }
        },
        computed: {
            saveStateLabel() {
                let vars = {file: this.file.name};
                if(this.saveState === 'error') {
                    return t('editor.state.error', vars);
                } else if(this.saveState === 'saving') {
                    return t('editor.state.saving', vars);
                } else if(!this.file.isSaved) {
                    return t('editor.state.unsaved', vars);
                }

                return t('editor.state.saved', vars);
            },
            closeLabel() {
                if(this.file.isSaved) {
                    return t('editor.close');
                }

                return t('editor.save-and-close', {file: this.file.name});
            },
            undoLabel() {
                return t('editor.undo');
            },
            lineWrappingLabel() {
                return t('editor.line-wrapping');
            },
            tooBigForEditorText() {
                return t(
                    'editor.too-large',
                    {file: this.file.name, size: formatFileSize(this.file.size), maxSize: formatFileSize(loadState('htmlviewer', 'maxEditorSize'))}
                );
            },
            tooBigForCodeEditorText() {
                return t(
                    'editor.too-large-for-code-editor',
                    {file: this.file.name, size: formatFileSize(this.file.size), maxSize: formatFileSize(loadState('htmlviewer', 'maxCodeEditorSize'))}
                );
            }
        },
        methods : {
            /**
             * Update the preview right away if there was no update within the preview interval,
             * otherwise update it once the interval is over
             */
            schedulePreview() {
                if(this.previewTimeout !== null) {
                    this.previewPending = true;
                    return;
                }

                this.file.updatePreview();
                this.previewTimeout = setTimeout(() => {
                    this.previewTimeout = null;
                    if(this.previewPending) {
                        this.previewPending = false;
                        this.schedulePreview();
                    }
                }, PREVIEW_INTERVAL);
            },
            cancelPreview() {
                if(this.previewTimeout !== null) {
                    clearTimeout(this.previewTimeout);
                    this.previewTimeout = null;
                }
                this.previewPending = false;
            },
            flushPreview() {
                const pending = this.previewPending;
                this.cancelPreview();
                if(pending) {
                    this.file.updatePreview();
                }
            },
            scheduleSave() {
                this.cancelSave();
                this.saveTimeout = setTimeout(() => {this.save().catch(console.error);}, AUTOSAVE_DELAY);
            },
            cancelSave() {
                if(this.saveTimeout !== null) {
                    clearTimeout(this.saveTimeout);
                    this.saveTimeout = null;
                }
            },
            async save() {
                if(this.saveState === 'saving') {
                    return;
                }

                this.cancelSave();
                this.saveState = 'saving';
                try {
                    await this.file.save();
                    this.saveState = 'saved';
                } catch(e) {
                    console.error('Could not save file', this.file, e);
                    this.saveState = 'error';
                }
            },
            async close() {
                this.flushPreview();
                if(!this.file.isSaved) {
                    await this.save();
                }
                this.$emit('close');
            },
            async undo() {
                this.cancelPreview();
                this.cancelSave();
                this.saveState = 'saving';
                await this.file.loadContent(true);
                this.model = this.file.raw;
                this.saveState = 'saved';
            }
        },
        watch   : {
            lineWrapping(value) {
                editorSettings.lineWrapping = value;
            },
            model(value) {
                if(this.file.raw === value) {
                    return;
                }

                this.file.setRaw(value);
                this.schedulePreview();
                this.scheduleSave();
            },
            file(value) {
                this.cancelPreview();
                this.cancelSave();
                this.model = value.raw;
                this.saveState = 'saved';
            }
        }
    };
</script>

<style lang="scss">
.htmlviewer-editor {
    width          : 50%;
    flex-shrink    : 0;
    display        : flex;
    flex-direction : column;

    .htmlviewer-editor-controls {
        padding         : .25rem .5rem;
        display         : flex;
        justify-content : flex-end;

        span {
            cursor : pointer;
        }
    }

    .notecard {
        margin : .5rem;
    }

    textarea {
        font-family : monospace;
        width       : 100%;
        flex-grow   : 1;
        cursor      : text
    }
}
</style>
