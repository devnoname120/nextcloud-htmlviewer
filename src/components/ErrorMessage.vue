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
    <div class="htmlviewer-error">{{ message }}</div>
</template>

<script>

    import {formatFileSize} from "@nextcloud/files";
    import {loadState} from "@nextcloud/initial-state";
    import {translate} from '@nextcloud/l10n';
    import File from '../models/File.js';

    export default {
        props   : {
            file: File
        },
        computed: {
            message() {
                let maxSize = formatFileSize(loadState('htmlviewer', 'maxSize'));

                return translate(
                    'htmlviewer',
                    'The file "{file}" ({size}) exceeds the maximum allowed size of {maxSize}.',
                    {file: this.file.name, size: formatFileSize(this.file.size), maxSize}
                );
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
}
</style>