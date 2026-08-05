<script setup>
import { computed, ref, watch } from 'vue';
import { Button, Dialog, InputText, Message, Password, Select, useToast } from 'primevue';
import { t } from '../utils/i18n.js';
import { RestoreApi, errorMessage } from '../api/connector.js';

const props = defineProps({
  visible: { type: Boolean, default: false },
  run: { type: Object, default: null },
});
const emit = defineEmits(['update:visible', 'restored']);

const toast = useToast();

// Восстановление необратимо, поэтому оно двухшаговое: сначала preflight
// проверяет архив и выдаёт код, и только с этим кодом запускается сама
// операция. Пароль вводится один раз и переиспользуется на обоих шагах.
const step = ref('check');
const busy = ref(false);
const error = ref('');
const password = ref('');
const scope = ref('all');
const confirmation = ref('');
const info = ref(null);

const scopes = computed(() => [
  { value: 'all', label: t('mxbackup_restore_scope_all') },
  { value: 'files', label: t('mxbackup_restore_scope_files') },
  { value: 'database', label: t('mxbackup_restore_scope_database') },
]);

const tokenMatches = computed(
  () => !!info.value && confirmation.value.trim() === String(info.value.confirmation || '')
);

watch(
  () => props.visible,
  (open) => {
    if (!open) return;
    step.value = 'check';
    busy.value = false;
    error.value = '';
    password.value = '';
    scope.value = 'all';
    confirmation.value = '';
    info.value = null;
  }
);

async function preflight() {
  busy.value = true;
  error.value = '';
  try {
    const res = await RestoreApi.preflight(props.run.id, password.value);
    info.value = res.object || {};
    step.value = 'confirm';
  } catch (e) {
    error.value = errorMessage(e, t('mxbackup_error'));
  } finally {
    busy.value = false;
  }
}

async function restore() {
  busy.value = true;
  error.value = '';
  try {
    const res = await RestoreApi.restore({
      id: props.run.id,
      password: password.value,
      scope: scope.value,
      confirmation: confirmation.value.trim(),
    });
    const safety = res.object?.report?.stats?.safety_backup || '';
    toast.add({
      severity: 'success',
      summary: t('mxbackup_restore'),
      detail: safety
        ? `${t('mxbackup_restore_finished')} ${t('mxbackup_restore_safety_backup')}: ${safety}`
        : t('mxbackup_restore_finished'),
      life: 15000,
    });
    emit('restored');
    emit('update:visible', false);
  } catch (e) {
    error.value = errorMessage(e, t('mxbackup_error'));
  } finally {
    busy.value = false;
  }
}
</script>

<template>
  <Dialog
    :visible="visible"
    modal
    :header="t('mxbackup_restore')"
    :style="{ width: '40rem' }"
    :breakpoints="{ '42rem': '95vw' }"
    :closable="!busy"
    @update:visible="emit('update:visible', $event)"
  >
    <Message severity="warn" :closable="false">{{ t('mxbackup_restore_safety_notice') }}</Message>
    <Message v-if="error" severity="error" :closable="false" class="mxb-form__error">{{ error }}</Message>

    <div class="mxb-form">
      <p class="mxb-target">
        <code>{{ run?.archive_name }}</code>
      </p>

      <template v-if="step === 'check'">
        <div class="mxb-field">
          <label for="mxb-restore-pass">{{ t('mxbackup_restore_password') }}</label>
          <Password
            id="mxb-restore-pass"
            v-model="password"
            :feedback="false"
            toggle-mask
            fluid
            autocomplete="off"
          />
          <small>{{ t('mxbackup_restore_password_desc') }}</small>
        </div>
      </template>

      <template v-else>
        <dl class="mxb-facts">
          <dt>{{ t('mxbackup_report_files') }}</dt>
          <dd>{{ info?.site_files || 0 }}</dd>
          <dt>{{ t('mxbackup_mode') }}</dt>
          <dd>{{ info?.manifest?.mode || '—' }}</dd>
          <dt>{{ t('mxbackup_restore_confirmation') }}</dt>
          <dd class="mxb-mono">{{ info?.confirmation }}</dd>
        </dl>

        <Message
          v-for="(warning, index) in (info?.warnings || [])"
          :key="index"
          severity="warn"
          :closable="false"
        >
          {{ warning }}
        </Message>

        <div class="mxb-field">
          <label for="mxb-restore-scope">{{ t('mxbackup_restore_scope') }}</label>
          <Select
            id="mxb-restore-scope"
            v-model="scope"
            :options="scopes"
            option-label="label"
            option-value="value"
          />
        </div>

        <div class="mxb-field">
          <label for="mxb-restore-token">{{ t('mxbackup_restore_confirmation') }}</label>
          <InputText id="mxb-restore-token" v-model="confirmation" autocomplete="off" />
          <small>{{ t('mxbackup_restore_type_token') }}</small>
        </div>
      </template>
    </div>

    <template #footer>
      <Button
        :label="t('mxbackup_cancel')"
        severity="secondary"
        outlined
        :disabled="busy"
        @click="emit('update:visible', false)"
      />
      <Button
        v-if="step === 'check'"
        :label="t('mxbackup_restore_check')"
        icon="pi pi-shield"
        :loading="busy"
        @click="preflight"
      />
      <Button
        v-else
        :label="t('mxbackup_restore')"
        icon="pi pi-replay"
        severity="danger"
        :loading="busy"
        :disabled="!tokenMatches"
        @click="restore"
      />
    </template>
  </Dialog>
</template>
