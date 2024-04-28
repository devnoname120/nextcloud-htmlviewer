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
        <iframe class="htmlviewer-iframe" referrerpolicy="no-referrer" credentialless="true" :sandbox="sandbox" :csp="csp" :src="file.content" v-if="file.loaded"/>
        <div class="htmlviewer-controls-container" v-if="showName">
            <div class="htmlviewer-controls">
                {{ file.name }}
            </div>
        </div>
    </div>
</template>

<script>
    import {loadState} from "@nextcloud/initial-state";
    import File from "../models/File.js";

    export default {
        props: {
            file    : File,
            showName: Boolean
        },
        data() {
            return {
                sandbox: loadState('htmlviewer', 'sandbox'),
                csp    : loadState('htmlviewer', 'csp')
            };
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

    .htmlviewer-controls-container {
        position        : absolute;
        bottom          : 1rem;
        left            : 0;
        right           : 0;
        display         : flex;
        justify-content : center;

        .htmlviewer-controls {
            padding       : 1rem;
            border-radius : var(--border-radius-pill);
            background    : var(--color-main-background-translucent);
        }
    }
}
</style>