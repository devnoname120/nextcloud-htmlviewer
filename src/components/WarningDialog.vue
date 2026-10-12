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
    <NcDialog :name="t('Warning')" no-close>
        <template #actions>
            <NcCheckboxRadioSwitch v-model="disableWarning" type="switch">{{ t('Do not warn again') }}</NcCheckboxRadioSwitch>
            <NcDialogButton @click="abort()" :label="t('Cancel')"/>
            <NcDialogButton @click="ok()" :label="t('Ok')" variant="primary"/>
        </template>
        {{ t('Opening HTML files in the browser can be a security risk. Do you want to proceed?') }}
    </NcDialog>
</template>

<script>
    import NcDialog from '@nextcloud/vue/components/NcDialog';
    import NcDialogButton from "@nextcloud/vue/components/NcDialogButton";
    import NcCheckboxRadioSwitch from "@nextcloud/vue/components/NcCheckboxRadioSwitch";
    import {translate} from "@nextcloud/l10n";
    import scriptWarning from "../utils/scriptWarning.js";

    export default {
        components: {
            NcDialog,
            NcDialogButton,
            NcCheckboxRadioSwitch
        },
        emits     : ['abort', 'confirm'],
        data() {
            return {
                disableWarning: scriptWarning.status()
            };
        },
        methods: {
            t(s, v) {
                return translate('htmlviewer', s, v);
            },
            abort() {
                this.$emit('abort');
            },
            ok() {
                if(this.disableWarning) {
                    scriptWarning.disable();
                }
                this.$emit('confirm');
            }
        }
    };
</script>

<style scoped lang="scss">

</style>