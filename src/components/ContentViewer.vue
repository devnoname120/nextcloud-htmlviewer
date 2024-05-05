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
    <div class="htmlviewer-content">
        <iframe class="htmlviewer-iframe" referrerpolicy="no-referrer" credentialless="true" :sandbox="sandbox" :csp="csp" :src="file.content"/>
        <div class="htmlviewer-label-container" v-if="showName">
            <div class="htmlviewer-label">
                {{ file.name }}
            </div>
        </div>
        <div class="htmlviewer-edit-container" v-if="showEdit">
            <NcButton type="secondary" :aria-label="editLabel" @click="$emit('edit')">
                <template #icon>
                    <PencilIcon :size="20"/>
                </template>
            </NcButton>
        </div>
    </div>
</template>

<script>
    import {translate} from '@nextcloud/l10n';
    import {loadState} from "@nextcloud/initial-state";
    import File from "../models/File.js";
    import NcButton from "@nextcloud/vue/dist/Components/NcButton.js";
    import PencilIcon from 'vue-material-design-icons/Pencil.vue';

    export default {
        components: {PencilIcon, NcButton},
        props     : {
            file    : File,
            showName: Boolean,
            showEdit: Boolean
        },
        data() {
            return {
                sandbox: loadState('htmlviewer', 'sandbox'),
                csp    : loadState('htmlviewer', 'csp')
            };
        },
        computed: {
            editLabel() {
                return translate('htmlviewer', 'Edit "{file}"', {file: this.file.name});
            }
        }
    };
</script>

<style scoped lang="scss">
.htmlviewer-content {
    width    : 100%;
    height   : 100%;
    position : relative;

    iframe.htmlviewer-iframe {
        width      : 100%;
        height     : 100%;
        box-sizing : border-box;
    }

    .htmlviewer-label-container {
        position        : absolute;
        bottom          : 1rem;
        left            : 0;
        right           : 0;
        display         : flex;
        justify-content : center;

        .htmlviewer-label {
            padding       : 1rem;
            border-radius : var(--border-radius-pill);
            background    : var(--color-main-background-translucent);
        }
    }

    .htmlviewer-edit-container {
        position        : absolute;
        bottom          : 1rem;
        left            : 0;
        right           : 1rem;
        display         : flex;
        justify-content : flex-end;
    }
}
</style>