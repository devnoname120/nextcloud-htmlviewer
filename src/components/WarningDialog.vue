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
    <NcDialog :name="t('warning.title')" no-close>
        <template #actions>
            <NcCheckboxRadioSwitch v-model="disableWarning" type="switch">{{ t('warning.disable') }}</NcCheckboxRadioSwitch>
            <NcDialogButton @click="abort()" :label="t('warning.cancel')"/>
            <NcDialogButton @click="ok()" :label="t('warning.confirm')" variant="primary"/>
        </template>
        {{ t('warning.message') }}
    </NcDialog>
</template>

<script>
    import NcDialog from '@nextcloud/vue/components/NcDialog';
    import NcDialogButton from "@nextcloud/vue/components/NcDialogButton";
    import NcCheckboxRadioSwitch from "@nextcloud/vue/components/NcCheckboxRadioSwitch";
    import {t} from "../utils/l10n.js";
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
            t,
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