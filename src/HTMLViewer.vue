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
    <div class="htmlviewer-container">
        <iframe referrerpolicy="no-referrer" credentialless="true" :sandbox="sandbox" :csp="csp" :src="src" v-if="contentLoaded"/>
        <div class="error" v-if="error">{{ error }}</div>
        <NcDialog v-if="hasDialog" :name="t('Warning')" :can-close="false">
            <template #actions>
                <NcCheckboxRadioSwitch :checked.sync="disableWarning" type="switch">{{ t('Do not warn again') }}</NcCheckboxRadioSwitch>
                <NcDialogButton @click="abort()" :label="t('Cancel')"/>
                <NcDialogButton @click="ok()" :label="t('Ok')" type="primary"/>
            </template>
            {{ t('Opening HTML files in the browser can be a security risk. Do you want to proceed?') }}
        </NcDialog>
        <NcLoadingIcon class="htmlviewer-loader" :size="64" v-if="showLoading && !contentLoaded"/>
    </div>
</template>

<script>
    import axios from '@nextcloud/axios';
    import {loadState} from '@nextcloud/initial-state';
    import {formatFileSize} from '@nextcloud/files';
    import {translate} from '@nextcloud/l10n';
    import NcDialog from '@nextcloud/vue/dist/Components/NcDialog.js';
    import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js';
    import NcDialogButton from '@nextcloud/vue/dist/Components/NcDialogButton.js';
    import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js';
    import scriptWarning from "./utils/scriptWarning";

    export default {
        components: {NcDialog, NcLoadingIcon, NcDialogButton, NcCheckboxRadioSwitch},
        props     : {
            size       : Number,
            source     : String,
            showLoading: {
                type   : Boolean,
                default: false
            }
        },
        data() {
            let disableWarning = scriptWarning.status(),
                allowJs        = loadState('htmlviewer', 'allowJs');

            return {
                contentLoaded: false,
                src          : null,
                allowJs,
                disableWarning,
                maxSize      : loadState('htmlviewer', 'maxSize'),
                csp          : loadState('htmlviewer', 'csp'),
                error        : null,
                hasDialog    : false,
                showWarning  : allowJs && !disableWarning
            };
        },
        mounted() {
            if(!this.showWarning) {
                this.loadFileContent()
                    .catch(console.error);
            } else {
                this.$nextTick(() => {
                    this.hasDialog = true;
                });
            }
        },
        computed: {
            sandbox() {
                if(this.allowJs) {
                    return 'allow-scripts allow-presentation allow-modals allow-downloads';
                }
                return '';
            }
        },
        methods : {
            async loadFileContent() {
                if(this.size > this.maxSize) {
                    this.error = this.t(
                        'The file "{file}" ({size}) exceeds the maximum allowed size of {maxSize}.',
                        {file: this.basename, size: formatFileSize(this.size), maxSize: formatFileSize(this.maxSize)}
                    );
                    this.$emit('update:loaded', true);
                    return;
                }

                const response = await axios.get(this.source);
                let content = response.data;

                if(this.allowJs) {
                    let nonce = loadState('htmlviewer', 'nonce');
                    content = response.data.replace(/\<script/g, `<script nonce="${nonce}"`);
                }

                let blob = new Blob([content], {type: "text/html"});
                this.src = URL.createObjectURL(blob);
                this.contentLoaded = true;
                this.$emit('update:loaded', true);
            },
            abort() {
                this.hasDialog = false;
                this.$parent.close();
            },
            t(s, v) {
                return translate('htmlviewer', s, v);
            },
            ok() {
                this.hasDialog = false;
                if(this.disableWarning) {
                    scriptWarning.disable();
                }
                this.loadFileContent()
                    .catch(console.error);
            }
        },

        watch: {
            source() {
                this.loadFileContent();
            }
        }
    };
</script>

<style scoped lang="scss">
.htmlviewer-container {
    width  : 100%;
    height : 100%;

    iframe {
        width      : 100%;
        height     : 100%;
        box-sizing : border-box;
    }

    .error {
        width      : 100%;
        text-align : center;
        padding    : 2rem 1rem;
        font-size  : 1.25rem;
        box-sizing : border-box;
    }

    .htmlviewer-loader {
        margin-top : 5rem;
    }
}
</style>