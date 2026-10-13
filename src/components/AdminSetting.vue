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
    <div class="htmlviewer-admin-setting">
        <div class="htmlviewer-admin-setting-control">
            <slot/>
        </div>
        <NcButton class="htmlviewer-admin-setting-reset"
                  variant="tertiary"
                  :aria-label="resetLabel"
                  :title="resetLabel"
                  :disabled="disabled"
                  @click="$emit('reset')"
                  v-if="customized">
            <template #icon>
                <NcIconSvgWrapper :path="mdiRestore" :size="20"/>
            </template>
        </NcButton>
    </div>
</template>

<script>
    import NcButton from '@nextcloud/vue/components/NcButton';
    import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper';
    import {mdiRestore} from '@mdi/js';
    import {t} from '../utils/l10n.js';

    /**
     * A setting in the admin settings with a button to restore its default value
     */
    export default {
        components: {NcButton, NcIconSvgWrapper},
        props     : {
            label     : {
                type    : String,
                required: true
            },
            customized: {
                type   : Boolean,
                default: false
            },
            disabled  : {
                type   : Boolean,
                default: false
            }
        },
        emits     : ['reset'],
        data() {
            return {mdiRestore};
        },
        computed: {
            resetLabel() {
                return t('admin.reset', {setting: this.label});
            }
        }
    };
</script>

<style scoped lang="scss">
.htmlviewer-admin-setting {
    display     : flex;
    align-items : flex-start;
    gap         : calc(var(--default-grid-baseline) * 2);
    max-width   : 800px;
    margin      : calc(var(--default-grid-baseline) * 3) 0;

    .htmlviewer-admin-setting-control {
        flex      : 1 1 auto;
        min-width : 0;
    }

    .htmlviewer-admin-setting-reset {
        flex : 0 0 auto;
    }
}
</style>
