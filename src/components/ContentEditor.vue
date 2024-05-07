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
        <div class="htmlviewer-editor-controls">
            <NcButton type="tertiary" :aria-label="undoLabel" @click="undo" :disabled="file.isSaved || saveState === 'saving'">
                <template #icon>
                    <ArrowULeftTopIcon :size="20"/>
                </template>
            </NcButton>
            <NcButton type="tertiary" :aria-label="saveStateLabel" @click="save">
                <template #icon>
                    <NcSavingIndicatorIcon :name="saveStateLabel" :error="saveState === 'error'" :saving="saveState === 'saving'"/>
                </template>
            </NcButton>
            <NcButton type="tertiary" :aria-label="closeLabel" @click="close">
                <template #icon>
                    <CloseIcon :size="20"/>
                </template>
            </NcButton>

        </div>
        <textarea v-model="model" :disabled="saveState === 'saving'"></textarea>
    </div>
</template>

<script>
    import File from "../models/File.js";
    import {translate} from '@nextcloud/l10n';
    import NcSavingIndicatorIcon from '@nextcloud/vue/dist/Components/NcSavingIndicatorIcon.js';
    import NcButton from "@nextcloud/vue/dist/Components/NcButton.js";
    import CloseIcon from 'vue-material-design-icons/Close.vue';
    import ArrowULeftTopIcon from 'vue-material-design-icons/ArrowULeftTop.vue';

    export default {
        components: {NcButton, NcSavingIndicatorIcon, CloseIcon, ArrowULeftTopIcon},
        props     : {
            file    : File,
            showName: Boolean
        },
        data() {
            return {
                model         : this.file.raw,
                previewTimeout: null,
                saveTimeout   : null,
                lastUpdate    : Date.now(),
                saveState     : 'saved'
            };
        },
        beforeDestroy() {
            if(!this.file.isSaved) {
                this.save().catch(console.error);
            }
        },
        computed: {
            saveStateLabel() {
                let text = '"{file}" is saved';
                if(this.saveState === 'error') {
                    text = 'Error saving "{file}"';
                } else if(this.saveState === 'saving') {
                    text = 'Saving "{file}"';
                } else if(!this.file.isSaved) {
                    text = 'Save "{file}"';
                }

                return translate('htmlviewer', text, {file: this.file.name});
            },
            closeLabel() {
                if(this.file.isSaved) {
                    return translate('htmlviewer', 'Close editor');
                }

                return translate('htmlviewer', 'Save "{file}" and close editor', {file: this.file.name});
            },
            undoLabel() {
                return translate('htmlviewer', 'Undo changes');
            }
        },
        methods : {
            updateContent() {
                if(this.lastUpdate > Date.now() - 1000) {
                    if(this.previewTimeout) {
                        clearTimeout(this.previewTimeout);
                    }
                    this.previewTimeout = setTimeout(() => {this.updateContent();}, 1250);
                    return;
                }

                if(this.file.raw !== this.model) {
                    this.file.setContent(this.model);
                    this.lastUpdate = Date.now();

                    if(this.saveTimeout) {
                        clearTimeout(this.saveTimeout);
                    }
                    this.saveTimeout = setTimeout(() => {this.save();}, 5000);
                }
            },
            async save() {
                if(this.saveState === 'saving') {
                    return;
                }

                if(this.previewTimeout) {
                    clearTimeout(this.previewTimeout);
                }

                if(this.saveTimeout) {
                    clearTimeout(this.saveTimeout);
                }

                this.saveState = 'saving';
                this.file.setContent(this.model);
                this.file.save()
                    .catch((e) => {
                        console.error('Could not save file', this.file, e);
                        this.saveState = 'error';
                    })
                    .then(() => {
                        this.saveState = 'saved';
                        this.lastUpdate = Date.now();
                    });
            },
            async close() {
                if(!this.file.isSaved) {
                    await this.save();
                }
                this.$emit('close');
            },
            async undo() {
                if(this.previewTimeout) {
                    clearTimeout(this.previewTimeout);
                }
                if(this.saveTimeout) {
                    clearTimeout(this.saveTimeout);
                }
                this.saveState = 'saving';
                await this.file.loadContent(true);
                this.model = this.file.raw;
                this.saveState = 'saved';
            }
        },
        watch   : {
            model() {
                this.updateContent();
            },
            file(value) {
                this.model = value.raw;
                if(this.previewTimeout) {
                    clearTimeout(this.previewTimeout);
                    this.previewTimeout = null;
                }
                if(this.saveTimeout) {
                    clearTimeout(this.saveTimeout);
                    this.saveTimeout = null;
                }
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

    textarea {
        font-family : monospace;
        width       : 100%;
        flex-grow   : 1;
    }
}
</style>