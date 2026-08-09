<script setup>
import { computed, ref, watch } from 'vue';
import { Button, Checkbox, Dialog, InputNumber, InputText, Message, Password, Select, Textarea, useToast } from 'primevue';
import { t } from '../utils/i18n.js';
import { ProfileApi, errorMessage } from '../api/connector.js';

const props = defineProps({
  visible: { type: Boolean, default: false },
  profile: { type: Object, default: null },
});
const emit = defineEmits(['update:visible', 'saved']);

const toast = useToast();
const saving = ref(false);
const error = ref('');

const empty = () => ({
  id: '',
  name: '',
  description: '',
  mode: 'custom',
  format: 'tar.gz',
  encryption_enabled: false,
  encryption_password: '',
  encryption_password_set: false,
  file_include: '*',
  file_exclude: '',
  standard_masking: false,
  active: true,
  remote_driver: '',
  remote_keep_local: 2,
  remote_retention_days: 0,
  remote_retention_count: 0,
  remote_s3_bucket: '',
  remote_s3_region: '',
  remote_s3_prefix: '',
  remote_s3_endpoint: '',
  remote_s3_storage_class: '',
  remote_s3_access_key: '',
  remote_s3_secret_key: '',
  remote_s3_secret_set: false,
});

const form = ref(empty());

const modes = computed(() => [
  { value: 'prod', label: t('mxbackup_mode_prod') },
  { value: 'dev', label: t('mxbackup_mode_dev') },
  { value: 'custom', label: t('mxbackup_mode_custom') },
]);
const formats = [
  { value: 'tar.gz', label: 'tar.gz' },
  { value: 'zip', label: 'ZIP' },
];

const isNew = computed(() => !props.profile);
// В dev-профиле стандартное обезличивание обязательно — ядро всё равно его
// включит, поэтому чекбокс блокируется, а не молча расходится с результатом.
const maskingLocked = computed(() => form.value.mode === 'dev');
// AES-256 доступен только для ZIP: у tar.gz шифрования содержимого нет.
const encryptionAvailable = computed(() => form.value.format === 'zip');

const remoteDrivers = computed(() => [
  { value: '', label: t('mxbackup_remote_off') },
  { value: 's3', label: 'S3' },
]);
const remoteEnabled = computed(() => form.value.remote_driver === 's3');

watch(
  () => [props.visible, props.profile],
  () => {
    if (!props.visible) return;
    error.value = '';
    // Секреты форма обратно не показывает: пустое поле означает «не менять».
    form.value = props.profile
      ? { ...empty(), ...props.profile, encryption_password: '', remote_s3_secret_key: '' }
      : empty();
  },
  { immediate: true }
);

watch(maskingLocked, (locked) => {
  if (locked) form.value.standard_masking = true;
});
watch(encryptionAvailable, (available) => {
  if (!available) form.value.encryption_enabled = false;
});

async function save() {
  saving.value = true;
  error.value = '';
  const payload = {
    id: form.value.id || '',
    name: (form.value.name || '').trim(),
    description: form.value.description || '',
    mode: form.value.mode,
    format: form.value.format,
    encryption_enabled: form.value.encryption_enabled ? 1 : 0,
    encryption_password: form.value.encryption_password || '',
    file_include: form.value.file_include || '*',
    file_exclude: form.value.file_exclude || '',
    standard_masking: form.value.standard_masking ? 1 : 0,
    active: form.value.active ? 1 : 0,
    remote_driver: form.value.remote_driver || '',
    remote_keep_local: Number(form.value.remote_keep_local) || 1,
    remote_retention_days: Number(form.value.remote_retention_days) || 0,
    remote_retention_count: Number(form.value.remote_retention_count) || 0,
    remote_s3_bucket: form.value.remote_s3_bucket || '',
    remote_s3_region: form.value.remote_s3_region || '',
    remote_s3_prefix: form.value.remote_s3_prefix || '',
    remote_s3_endpoint: form.value.remote_s3_endpoint || '',
    remote_s3_storage_class: form.value.remote_s3_storage_class || '',
    remote_s3_access_key: form.value.remote_s3_access_key || '',
    // Пустой секрет — «оставить прежний», ядро отличает это от стирания.
    remote_s3_secret_key: form.value.remote_s3_secret_key || '',
  };
  try {
    if (isNew.value) {
      delete payload.id;
      await ProfileApi.create(payload);
    } else {
      await ProfileApi.update(payload);
    }
    toast.add({ severity: 'success', summary: t('mxbackup_profiles'), detail: t('mxbackup_profile_saved'), life: 5000 });
    emit('saved');
  } catch (e) {
    error.value = errorMessage(e, t('mxbackup_error'));
  } finally {
    saving.value = false;
  }
}
</script>

<template>
  <Dialog
    :visible="visible"
    modal
    :header="isNew ? t('mxbackup_add_profile') : t('mxbackup_edit_profile')"
    :style="{ width: '46rem' }"
    :breakpoints="{ '48rem': '95vw' }"
    @update:visible="emit('update:visible', $event)"
  >
    <Message v-if="error" severity="error" :closable="false" class="mxb-form__error">{{ error }}</Message>

    <div class="mxb-form">
      <div class="mxb-field">
        <label for="mxb-profile-name">{{ t('mxbackup_name') }}</label>
        <InputText id="mxb-profile-name" v-model="form.name" autocomplete="off" />
        <small>{{ t('mxbackup_invalid_name') }}</small>
      </div>

      <div class="mxb-field mxb-field--half">
        <label for="mxb-profile-mode">{{ t('mxbackup_mode') }}</label>
        <Select
          id="mxb-profile-mode"
          v-model="form.mode"
          :options="modes"
          option-label="label"
          option-value="value"
        />
      </div>

      <div class="mxb-field mxb-field--half">
        <label for="mxb-profile-format">{{ t('mxbackup_format') }}</label>
        <Select
          id="mxb-profile-format"
          v-model="form.format"
          :options="formats"
          option-label="label"
          option-value="value"
        />
      </div>

      <div class="mxb-field">
        <label for="mxb-profile-desc">{{ t('mxbackup_description') }}</label>
        <Textarea id="mxb-profile-desc" v-model="form.description" rows="2" auto-resize />
      </div>

      <fieldset class="mxb-fieldset">
        <legend>{{ t('mxbackup_encryption') }}</legend>
        <div class="mxb-check">
          <Checkbox
            v-model="form.encryption_enabled"
            input-id="mxb-profile-enc"
            binary
            :disabled="!encryptionAvailable"
          />
          <label for="mxb-profile-enc">{{ t('mxbackup_encryption') }}</label>
        </div>
        <small>{{ t('mxbackup_encryption_desc') }}</small>

        <div v-if="form.encryption_enabled" class="mxb-field">
          <label for="mxb-profile-pass">{{ t('mxbackup_encryption_password') }}</label>
          <Password
            id="mxb-profile-pass"
            v-model="form.encryption_password"
            :feedback="false"
            :prompt-label="t('mxbackup_encryption_password')"
            toggle-mask
            fluid
            autocomplete="new-password"
          />
          <small>
            {{ form.encryption_password_set
              ? t('mxbackup_encryption_password_keep_desc')
              : t('mxbackup_encryption_password_desc') }}
          </small>
        </div>
      </fieldset>

      <fieldset class="mxb-fieldset">
        <legend>{{ t('mxbackup_files') }}</legend>
        <div class="mxb-field">
          <label for="mxb-profile-include">{{ t('mxbackup_file_include') }}</label>
          <Textarea id="mxb-profile-include" v-model="form.file_include" rows="3" />
          <small>{{ t('mxbackup_file_include_desc') }}</small>
        </div>
        <div class="mxb-field">
          <label for="mxb-profile-exclude">{{ t('mxbackup_file_exclude') }}</label>
          <Textarea id="mxb-profile-exclude" v-model="form.file_exclude" rows="4" />
          <small>{{ t('mxbackup_file_exclude_desc') }}</small>
        </div>
      </fieldset>

      <fieldset class="mxb-fieldset">
        <legend>{{ t('mxbackup_remote') }}</legend>

        <div class="mxb-field">
          <label for="mxb-profile-remote-driver">{{ t('mxbackup_remote_driver') }}</label>
          <Select
            id="mxb-profile-remote-driver"
            v-model="form.remote_driver"
            :options="remoteDrivers"
            option-label="label"
            option-value="value"
            fluid
          />
          <small>{{ t('mxbackup_remote_driver_desc') }}</small>
        </div>

        <template v-if="remoteEnabled">
          <div class="mxb-field">
            <label for="mxb-profile-remote-bucket">{{ t('mxbackup_remote_s3_bucket') }}</label>
            <InputText id="mxb-profile-remote-bucket" v-model="form.remote_s3_bucket" fluid />
          </div>

          <div class="mxb-field">
            <label for="mxb-profile-remote-region">{{ t('mxbackup_remote_s3_region') }}</label>
            <InputText id="mxb-profile-remote-region" v-model="form.remote_s3_region" fluid />
            <small>{{ t('mxbackup_remote_s3_region_desc') }}</small>
          </div>

          <div class="mxb-field">
            <label for="mxb-profile-remote-prefix">{{ t('mxbackup_remote_s3_prefix') }}</label>
            <InputText id="mxb-profile-remote-prefix" v-model="form.remote_s3_prefix" fluid />
            <small>{{ t('mxbackup_remote_s3_prefix_desc') }}</small>
          </div>

          <div class="mxb-field">
            <label for="mxb-profile-remote-endpoint">{{ t('mxbackup_remote_s3_endpoint') }}</label>
            <InputText id="mxb-profile-remote-endpoint" v-model="form.remote_s3_endpoint" fluid />
            <small>{{ t('mxbackup_remote_s3_endpoint_desc') }}</small>
          </div>

          <div class="mxb-field">
            <label for="mxb-profile-remote-class">{{ t('mxbackup_remote_s3_storage_class') }}</label>
            <InputText id="mxb-profile-remote-class" v-model="form.remote_s3_storage_class" fluid />
            <small>{{ t('mxbackup_remote_s3_storage_class_desc') }}</small>
          </div>

          <div class="mxb-field">
            <label for="mxb-profile-remote-key">{{ t('mxbackup_remote_s3_access_key') }}</label>
            <InputText id="mxb-profile-remote-key" v-model="form.remote_s3_access_key" fluid autocomplete="off" />
            <small>{{ t('mxbackup_remote_s3_access_key_desc') }}</small>
          </div>

          <div class="mxb-field">
            <label for="mxb-profile-remote-secret">{{ t('mxbackup_remote_s3_secret_key') }}</label>
            <Password
              id="mxb-profile-remote-secret"
              v-model="form.remote_s3_secret_key"
              :feedback="false"
              toggle-mask
              fluid
              autocomplete="new-password"
            />
            <small>
              {{ form.remote_s3_secret_set
                ? t('mxbackup_remote_s3_secret_keep_desc')
                : t('mxbackup_remote_s3_secret_desc') }}
            </small>
          </div>

          <div class="mxb-field">
            <label for="mxb-profile-remote-keep">{{ t('mxbackup_remote_keep_local') }}</label>
            <InputNumber id="mxb-profile-remote-keep" v-model="form.remote_keep_local" :min="1" fluid />
            <small>{{ t('mxbackup_remote_keep_local_desc') }}</small>
          </div>

          <div class="mxb-field">
            <label for="mxb-profile-remote-days">{{ t('mxbackup_remote_retention_days') }}</label>
            <InputNumber id="mxb-profile-remote-days" v-model="form.remote_retention_days" :min="0" fluid />
          </div>

          <div class="mxb-field">
            <label for="mxb-profile-remote-count">{{ t('mxbackup_remote_retention_count') }}</label>
            <InputNumber id="mxb-profile-remote-count" v-model="form.remote_retention_count" :min="0" fluid />
            <small>{{ t('mxbackup_remote_retention_desc') }}</small>
          </div>
        </template>
      </fieldset>

      <div class="mxb-check">
        <Checkbox
          v-model="form.standard_masking"
          input-id="mxb-profile-masking"
          binary
          :disabled="maskingLocked"
        />
        <label for="mxb-profile-masking">{{ t('mxbackup_standard_masking') }}</label>
      </div>
      <small class="mxb-field__hint">{{ t('mxbackup_standard_masking_desc') }}</small>

      <div class="mxb-check">
        <Checkbox v-model="form.active" input-id="mxb-profile-active" binary />
        <label for="mxb-profile-active">{{ t('mxbackup_active') }}</label>
      </div>
    </div>

    <template #footer>
      <Button
        :label="t('mxbackup_cancel')"
        severity="secondary"
        outlined
        :disabled="saving"
        @click="emit('update:visible', false)"
      />
      <Button :label="t('mxbackup_save')" icon="pi pi-check" :loading="saving" @click="save" />
    </template>
  </Dialog>
</template>
