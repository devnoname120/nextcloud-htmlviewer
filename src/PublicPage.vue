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
    <div class="htmlviewer-public-container" v-if="available">
        <NcModal :open.sync="showFile" :name="filename" size="full" :out-transition="true" @close="closing()" v-if="showFile">
            <HTMLViewer :size="filesize" :source="downloadURL" :show-loading="true"/>
        </NcModal>
        <div class="htmlviewer-cta">
            <NcButton :aria-label="t('htmlviewer', 'View')" @click="showFile = true">
                <template #icon>
                    <EyeIcon :size="20"/>
                </template>
                <template>{{ t('htmlviewer', 'View') }}</template>
            </NcButton>
        </div>
    </div>
</template>

<script>
    import HTMLViewer from "./HTMLViewer.vue";
    import NcButton from '@nextcloud/vue/dist/Components/NcButton.js';
    import EyeIcon from 'vue-material-design-icons/Eye.vue';
    import NcModal from '@nextcloud/vue/dist/Components/NcModal.js';
    import {loadState} from "@nextcloud/initial-state";

    export default {
        el        : '#htmlviewer-open',
        components: {
            HTMLViewer,
            NcButton,
            EyeIcon,
            NcModal
        },
        data() {
            let filesize = parseInt(document.getElementById('filesize').value);

            return {
                showFile   : false,
                filename   : document.getElementById('filename').value,
                filesize,
                downloadURL: document.getElementById('downloadURL').value,
                available  : filesize <= loadState('htmlviewer', 'maxSize'),
                loaded     : false
            };
        },
        methods: {
            closing() {
                this.loaded = false;
                this.$nextTick(() => {
                    this.showFile = false;
                });
            }
        }

    };
</script>

<style scoped lang="scss">
.htmlviewer-public-container {
    .htmlviewer-cta {
        display         : flex;
        justify-content : center;
    }
}
</style>