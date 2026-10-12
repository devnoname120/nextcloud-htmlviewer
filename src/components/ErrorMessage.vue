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
    <div class="htmlviewer-error">
        {{ message }}
        <NcButton class="htmlviewer-download" variant="primary" :aria-label="download" :href="file.source" :download="file.name" v-if="file.source">
            {{ download }}
            <template #icon>
                <NcIconSvgWrapper :path="mdiDownload" :size="20"/>
            </template>
        </NcButton>
    </div>
</template>

<script>

    import {formatFileSize} from "@nextcloud/files";
    import {loadState} from "@nextcloud/initial-state";
    import {t} from '../utils/l10n.js';
    import File from '../models/File.js';
    import NcButton from "@nextcloud/vue/components/NcButton";
    import NcIconSvgWrapper from "@nextcloud/vue/components/NcIconSvgWrapper";
    import {mdiDownload} from '@mdi/js';

    export default {
        components: {NcButton, NcIconSvgWrapper},
        props     : {
            file: File
        },
        data() {
            return {mdiDownload};
        },
        computed  : {
            message() {
                let maxSize = formatFileSize(loadState('htmlviewer', 'maxSize'));

                return t(
                    'viewer.file-too-large',
                    {file: this.file.name, size: formatFileSize(this.file.size), maxSize}
                );
            },
            download() {
                return t('viewer.download');
            }
        }
    };
</script>

<style scoped lang="scss">
.htmlviewer-error {
    width      : 100%;
    text-align : center;
    padding    : 2rem 1rem;
    font-size  : 1.25rem;
    box-sizing : border-box;
    color      : var(--color-error-text);

    .htmlviewer-download {
        margin : 2rem auto 1rem;
    }
}
</style>