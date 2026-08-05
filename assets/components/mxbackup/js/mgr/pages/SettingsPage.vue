<script setup>
import { onMounted, ref } from 'vue';
import { Button, Checkbox, InputNumber, InputText, Message, Select, useToast } from 'primevue';
import { t } from '../utils/i18n.js';
import { ConfigApi, config, errorMessage } from '../api/connector.js';
import { useProfiles } from '../composables/useProfiles.js';

const toast = useToast();
const can = config().permissions || {};
const { profiles, load: loadProfiles } = useProfiles();

const loading = ref(true);
const saving = ref(false);
const error = ref('');

const form = ref({
  storage_path: '',
  config_dir: '',
  config_path: '',
  default_profile: '',
  archive_format: 'tar.gz',
  mail_enabled: false,
  mail_to: '',
  mail_max_attachment_mb: 10,
  retention_days: 30,
  retention_count: 10,
  lock_ttl_minutes: 720,
});

const formats = [
  { value: 'tar.gz', label: 'tar.gz' },
  { value: 'zip', label: 'ZIP' },
];

onMounted(async () => {
  await loadProfiles();
  await load();
});

async function load() {
  loading.value = true;
  error.value = '';
  try {
    const res = await ConfigApi.get();
    const values = res.object || {};
    form.value = {
      ...form.value,
      ...values,
      mail_enabled: ['1', 1, true, 'true'].includes(values.mail_enabled),
      mail_max_attachment_mb: Number(values.mail_max_attachment_mb || 0),
      retention_days: Number(values.retention_days || 0),
      retention_count: Number(values.retention_count || 0),
      lock_ttl_minutes: Number(values.lock_ttl_minutes || 0),
    };
  } catch (e) {
    error.value = errorMessage(e, t('mxbackup_error'));
  } finally {
    loading.value = false;
  }
}

async function save() {
  saving.value = true;
  error.value = '';
  try {
    await ConfigApi.update({ ...form.value, mail_enabled: form.value.mail_enabled ? 1 : 0 });
    toast.add({ severity: 'success', summary: t('mxbackup_settings'), detail: t('mxbackup_settings_saved'), life: 5000 });
    await load();
  } catch (e) {
    error.value = errorMessage(e, t('mxbackup_error'));
  } finally {
    saving.value = false;
  }
}
</script>

<template>
  <div class="mxb-page mxb-settings">
    <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>

    <form class="mxb-form mxb-form--settings" @submit.prevent="save">
      <fieldset class="mxb-fieldset">
        <legend>{{ t('area_mxbackup_general') }}</legend>

        <div class="mxb-field">
          <label for="mxb-set-storage">{{ t('setting_mxbackup.storage_path') }}</label>
          <InputText id="mxb-set-storage" v-model="form.storage_path" :disabled="loading" />
          <small>{{ t('setting_mxbackup.storage_path_desc') }}</small>
        </div>

        <div class="mxb-field">
          <label for="mxb-set-configdir">{{ t('setting_mxbackup.config_dir') }}</label>
          <InputText id="mxb-set-configdir" v-model="form.config_dir" :disabled="loading" />
          <small>{{ t('setting_mxbackup.config_dir_desc') }}</small>
        </div>

        <div class="mxb-field">
          <label for="mxb-set-configpath">{{ t('setting_mxbackup.config_path') }}</label>
          <InputText id="mxb-set-configpath" v-model="form.config_path" :disabled="loading" />
          <small>{{ t('setting_mxbackup.config_path_desc') }}</small>
        </div>

        <div class="mxb-field mxb-field--half">
          <label for="mxb-set-profile">{{ t('setting_mxbackup.default_profile') }}</label>
          <Select
            id="mxb-set-profile"
            v-model="form.default_profile"
            :options="profiles"
            option-label="name"
            option-value="name"
            :disabled="loading"
          />
          <small>{{ t('setting_mxbackup.default_profile_desc') }}</small>
        </div>

        <div class="mxb-field mxb-field--half">
          <label for="mxb-set-format">{{ t('setting_mxbackup.archive_format') }}</label>
          <Select
            id="mxb-set-format"
            v-model="form.archive_format"
            :options="formats"
            option-label="label"
            option-value="value"
            :disabled="loading"
          />
          <small>{{ t('setting_mxbackup.archive_format_desc') }}</small>
        </div>
      </fieldset>

      <fieldset class="mxb-fieldset">
        <legend>{{ t('area_mxbackup_mail') }}</legend>

        <div class="mxb-check">
          <Checkbox v-model="form.mail_enabled" input-id="mxb-set-mail" binary :disabled="loading" />
          <label for="mxb-set-mail">{{ t('setting_mxbackup.mail_enabled') }}</label>
        </div>
        <small class="mxb-field__hint">{{ t('setting_mxbackup.mail_enabled_desc') }}</small>

        <div class="mxb-field">
          <label for="mxb-set-mailto">{{ t('setting_mxbackup.mail_to') }}</label>
          <InputText id="mxb-set-mailto" v-model="form.mail_to" :disabled="loading || !form.mail_enabled" />
          <small>{{ t('setting_mxbackup.mail_to_desc') }}</small>
        </div>

        <div class="mxb-field mxb-field--half">
          <label for="mxb-set-attach">{{ t('setting_mxbackup.mail_max_attachment_mb') }}</label>
          <InputNumber
            id="mxb-set-attach"
            v-model="form.mail_max_attachment_mb"
            :min="0"
            :max-fraction-digits="1"
            fluid
            :disabled="loading || !form.mail_enabled"
          />
          <small>{{ t('setting_mxbackup.mail_max_attachment_mb_desc') }}</small>
        </div>
      </fieldset>

      <fieldset class="mxb-fieldset">
        <legend>{{ t('area_mxbackup_retention') }}</legend>

        <div class="mxb-field mxb-field--half">
          <label for="mxb-set-days">{{ t('setting_mxbackup.retention_days') }}</label>
          <InputNumber id="mxb-set-days" v-model="form.retention_days" :min="0" fluid :disabled="loading" />
          <small>{{ t('setting_mxbackup.retention_days_desc') }}</small>
        </div>

        <div class="mxb-field mxb-field--half">
          <label for="mxb-set-count">{{ t('setting_mxbackup.retention_count') }}</label>
          <InputNumber id="mxb-set-count" v-model="form.retention_count" :min="0" fluid :disabled="loading" />
          <small>{{ t('setting_mxbackup.retention_count_desc') }}</small>
        </div>

        <div class="mxb-field mxb-field--half">
          <label for="mxb-set-lock">{{ t('setting_mxbackup.lock_ttl_minutes') }}</label>
          <InputNumber id="mxb-set-lock" v-model="form.lock_ttl_minutes" :min="0" fluid :disabled="loading" />
          <small>{{ t('setting_mxbackup.lock_ttl_minutes_desc') }}</small>
        </div>
      </fieldset>

      <div v-if="can.manage" class="mxb-form__actions">
        <Button
          type="submit"
          :label="t('mxbackup_save')"
          icon="pi pi-check"
          :loading="saving"
          :disabled="loading"
        />
      </div>
    </form>
  </div>
</template>
