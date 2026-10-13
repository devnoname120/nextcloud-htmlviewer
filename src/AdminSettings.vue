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
    <NcSettingsSection :name="t('admin.title')" :description="t('admin.description')" :doc-url="docUrl">
        <h3>{{ t('admin.section.viewer') }}</h3>
        <AdminSetting :label="t('admin.new-tab-links')" :customized="isCustomized('newTabLinks')" :disabled="saving.newTabLinks" @reset="reset('newTabLinks')">
            <NcCheckboxRadioSwitch :model-value="drafts.newTabLinks" type="switch" :disabled="saving.newTabLinks" @update:model-value="toggle('newTabLinks', $event)">
                {{ t('admin.new-tab-links') }}
                <template #description>{{ t('admin.new-tab-links.hint') }}</template>
            </NcCheckboxRadioSwitch>
        </AdminSetting>
        <AdminSetting :label="t('admin.allow-js')" :customized="isCustomized('allowJs')" :disabled="saving.allowJs" @reset="reset('allowJs')">
            <NcCheckboxRadioSwitch :model-value="drafts.allowJs" type="switch" :disabled="saving.allowJs" @update:model-value="toggle('allowJs', $event)">
                {{ t('admin.allow-js') }}
                <template #description>{{ t('admin.allow-js.hint') }}</template>
            </NcCheckboxRadioSwitch>
            <NcNoteCard type="warning" :text="t('admin.allow-js.warning')" v-if="settings.allowJs"/>
        </AdminSetting>
        <AdminSetting :label="t('admin.direct-editing')" :customized="isCustomized('directEditing')" :disabled="saving.directEditing" @reset="reset('directEditing')">
            <NcCheckboxRadioSwitch :model-value="drafts.directEditing" type="switch" :disabled="saving.directEditing" @update:model-value="toggle('directEditing', $event)">
                {{ t('admin.direct-editing') }}
                <template #description>{{ t('admin.direct-editing.hint') }}</template>
            </NcCheckboxRadioSwitch>
        </AdminSetting>

        <h3>{{ t('admin.section.size') }}</h3>
        <AdminSetting :label="t('admin.max-size')" :customized="isCustomized('maxSize')" :disabled="saving.maxSize" @reset="reset('maxSize')">
            <label class="htmlviewer-admin-label" for="htmlviewer-admin-maxSize">{{ t('admin.max-size') }}</label>
            <NcTextField v-model="drafts.maxSize"
                         id="htmlviewer-admin-maxSize"
                         label-outside
                         type="number"
                         min="0"
                         :max="maxMegabytes"
                         step="1"
                         :helper-text="t('admin.max-size.hint')"
                         :disabled="saving.maxSize"
                         @change="saveSize('maxSize')"/>
        </AdminSetting>
        <AdminSetting :label="t('admin.max-editor-size')" :customized="isCustomized('maxEditorSize')" :disabled="saving.maxEditorSize" @reset="reset('maxEditorSize')">
            <label class="htmlviewer-admin-label" for="htmlviewer-admin-maxEditorSize">{{ t('admin.max-editor-size') }}</label>
            <NcTextField v-model="drafts.maxEditorSize"
                         id="htmlviewer-admin-maxEditorSize"
                         label-outside
                         type="number"
                         min="0"
                         :max="maxMegabytes"
                         step="1"
                         :helper-text="t('admin.max-editor-size.hint')"
                         :disabled="saving.maxEditorSize"
                         @change="saveSize('maxEditorSize')"/>
        </AdminSetting>
        <AdminSetting :label="t('admin.max-code-editor-size')" :customized="isCustomized('maxCodeEditorSize')" :disabled="saving.maxCodeEditorSize" @reset="reset('maxCodeEditorSize')">
            <label class="htmlviewer-admin-label" for="htmlviewer-admin-maxCodeEditorSize">{{ t('admin.max-code-editor-size') }}</label>
            <NcTextField v-model="drafts.maxCodeEditorSize"
                         id="htmlviewer-admin-maxCodeEditorSize"
                         label-outside
                         type="number"
                         min="0"
                         :max="maxMegabytes"
                         step="1"
                         :helper-text="t('admin.max-code-editor-size.hint')"
                         :disabled="saving.maxCodeEditorSize"
                         @change="saveSize('maxCodeEditorSize')"/>
        </AdminSetting>

        <h3>{{ t('admin.section.policy') }}</h3>
        <p class="htmlviewer-admin-description">{{ t('admin.section.policy.description') }}</p>
        <AdminSetting :label="t('admin.csp')" :customized="isCustomized('csp')" :disabled="saving.csp" @reset="reset('csp')">
            <label class="htmlviewer-admin-label" for="htmlviewer-admin-csp">{{ t('admin.csp') }}</label>
            <NcTextField v-model="drafts.csp"
                         id="htmlviewer-admin-csp"
                         label-outside
                         :placeholder="defaultPolicy.csp"
                         :helper-text="t('admin.csp.hint')"
                         :maxlength="maxPolicyLength"
                         :disabled="saving.csp"
                         spellcheck="false"
                         @change="save('csp')"/>
        </AdminSetting>
        <AdminSetting :label="t('admin.sandbox')" :customized="isCustomized('sandbox')" :disabled="saving.sandbox" @reset="reset('sandbox')">
            <label class="htmlviewer-admin-label" for="htmlviewer-admin-sandbox">{{ t('admin.sandbox') }}</label>
            <NcTextField v-model="drafts.sandbox"
                         id="htmlviewer-admin-sandbox"
                         label-outside
                         :placeholder="defaultPolicy.sandbox || t('admin.sandbox.none')"
                         :helper-text="t('admin.sandbox.hint')"
                         :maxlength="maxPolicyLength"
                         :disabled="saving.sandbox"
                         spellcheck="false"
                         @change="save('sandbox')"/>
        </AdminSetting>
    </NcSettingsSection>
</template>

<script>
    import axios from '@nextcloud/axios';
    import {showError, showSuccess} from '@nextcloud/dialogs';
    import {loadState} from '@nextcloud/initial-state';
    import {generateUrl} from '@nextcloud/router';
    import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch';
    import NcNoteCard from '@nextcloud/vue/components/NcNoteCard';
    import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection';
    import NcTextField from '@nextcloud/vue/components/NcTextField';
    import AdminSetting from './components/AdminSetting.vue';
    import {t} from './utils/l10n.js';

    /**
     * Must match AppSettingValue::MAX_MEGABYTES and AppSettingValue::MAX_POLICY_LENGTH
     */
    const MAX_MEGABYTES = 1024 * 1024;
    const MAX_POLICY_LENGTH = 4096;

    /**
     * The app settings in the administration settings.
     * Every change is saved immediately, the CSP and the sandbox use the default policy when they are empty.
     */
    export default {
        components: {
            AdminSetting,
            NcCheckboxRadioSwitch,
            NcNoteCard,
            NcSettingsSection,
            NcTextField
        },
        data() {
            let settings = loadState('htmlviewer', 'appSettings');

            return {
                settings,
                drafts         : this.createDrafts(settings),
                defaults       : loadState('htmlviewer', 'defaultAppSettings'),
                saving         : {},
                docUrl         : 'https://git.mdns.eu/nextcloud/htmlviewer/-/blob/master/README.md#settings',
                maxMegabytes   : MAX_MEGABYTES,
                maxPolicyLength: MAX_POLICY_LENGTH
            };
        },
        computed: {
            defaultPolicy() {
                return loadState('htmlviewer', this.settings.allowJs ? 'defaultJsIframePolicy':'defaultIframePolicy');
            }
        },
        methods : {
            t,
            createDrafts(settings) {
                return {...settings, csp: settings.csp ?? '', sandbox: settings.sandbox ?? ''};
            },
            isCustomized(key) {
                return this.settings[key] !== this.defaults[key];
            },
            toggle(key, value) {
                this.drafts[key] = value;
                this.save(key);
            },
            saveSize(key) {
                let value = this.drafts[key];
                if(typeof value === 'string') {
                    value = value.trim() === '' ? NaN:Number(value);
                }

                if(!Number.isInteger(value) || value < 0 || value > MAX_MEGABYTES) {
                    showError(t('admin.invalid-size', {max: MAX_MEGABYTES}));
                    this.drafts[key] = this.settings[key];
                    return;
                }

                this.drafts[key] = value;
                this.save(key);
            },
            async save(key) {
                if(this.drafts[key] === this.createDrafts(this.settings)[key]) {
                    return;
                }

                await this.sendRequest(
                    key,
                    () => axios.put(generateUrl('/apps/htmlviewer/settings/app/{key}', {key}), {value: this.drafts[key]}),
                    t('admin.saved')
                );
            },
            async reset(key) {
                await this.sendRequest(
                    key,
                    () => axios.delete(generateUrl('/apps/htmlviewer/settings/app/{key}', {key})),
                    t('admin.reset-done')
                );
            },
            /**
             * Only the changed setting is updated from the response, other settings may be edited at the same time
             *
             * @param {String} key
             * @param {function(): Promise} request
             * @param {String} message
             */
            async sendRequest(key, request, message) {
                this.saving[key] = true;
                try {
                    let {data} = await request();
                    this.settings[key] = data[key];
                    showSuccess(message);
                } catch(e) {
                    console.error(`Could not save setting ${key}`, e);
                    showError(e.response?.status === 400 ? t('admin.invalid-value'):t('admin.save-failed'));
                } finally {
                    this.drafts[key] = this.createDrafts(this.settings)[key];
                    this.saving[key] = false;
                }
            }
        }
    };
</script>

<style scoped lang="scss">
h3 {
    margin-top : calc(var(--default-grid-baseline) * 6);

    &:first-of-type {
        margin-top : 0;
    }
}

.htmlviewer-admin-label {
    display     : block;
    font-weight : bold;
    margin      : 0 0 calc(var(--default-grid-baseline) * 1);
}

.htmlviewer-admin-description {
    max-width : 800px;
    color     : var(--color-text-maxcontrast);
}
</style>
