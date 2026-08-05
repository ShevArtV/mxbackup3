<script setup>
import { computed, ref, watch } from 'vue';
import { Button, Dialog, InputNumber, InputText, Message, Select, Textarea, useToast } from 'primevue';
import { t } from '../utils/i18n.js';
import { RuleApi, errorMessage } from '../api/connector.js';

const props = defineProps({
  visible: { type: Boolean, default: false },
  row: { type: Object, default: null },
  profile: { type: String, default: '' },
  table: { type: String, default: '' },
});
const emit = defineEmits(['update:visible', 'saved']);

const toast = useToast();
const saving = ref(false);
const error = ref('');

const form = ref({ action: 'mask', json_path: '', value: '', priority: 100 });

const actions = computed(() => [
  { value: 'mask', label: t('mxbackup_action_mask') },
  { value: 'hide', label: t('mxbackup_action_hide') },
  { value: 'hash', label: t('mxbackup_action_hash') },
  { value: 'replace', label: t('mxbackup_action_replace') },
]);

const isCustom = computed(() => props.row?.source === 'custom' && props.row?.rule_id);
const needsValue = computed(() => form.value.action === 'replace');

watch(
  () => [props.visible, props.row],
  () => {
    if (!props.visible || !props.row) return;
    error.value = '';
    form.value = {
      action: props.row.action || 'mask',
      json_path: props.row.json_path || '',
      value: props.row.value === null || props.row.value === undefined ? '' : String(props.row.value),
      priority: props.row.priority || 100,
    };
  },
  { immediate: true }
);

async function save() {
  saving.value = true;
  error.value = '';
  // JSON-путь дописывается к цели третьим сегментом: table.column.path.
  const jsonPath = String(form.value.json_path || '').replace(/^\$?\.?/, '');
  const params = {
    profile_id: props.profile,
    target_type: jsonPath ? 'json_path' : 'column',
    target: `${props.table}.${props.row.column}${jsonPath ? '.' + jsonPath : ''}`,
    // Не `action`: в коннекторе MODX этот параметр занят именем процессора.
    rule_action: form.value.action,
    value: form.value.value,
    priority: form.value.priority,
    active: 1,
  };
  try {
    if (isCustom.value) {
      await RuleApi.update({ ...params, id: props.row.rule_id });
    } else {
      await RuleApi.create(params);
    }
    toast.add({ severity: 'success', summary: t('mxbackup_masking'), detail: t('mxbackup_rule_saved'), life: 5000 });
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
    :header="isCustom ? t('mxbackup_edit_rule') : t('mxbackup_add_rule')"
    :style="{ width: '38rem' }"
    :breakpoints="{ '40rem': '95vw' }"
    @update:visible="emit('update:visible', $event)"
  >
    <Message v-if="error" severity="error" :closable="false" class="mxb-form__error">{{ error }}</Message>

    <div class="mxb-form">
      <p class="mxb-target">
        <code>{{ table }}.{{ row?.column }}</code>
      </p>

      <div class="mxb-field">
        <label for="mxb-rule-action">{{ t('mxbackup_masking_action') }}</label>
        <Select
          id="mxb-rule-action"
          v-model="form.action"
          :options="actions"
          option-label="label"
          option-value="value"
        />
      </div>

      <div class="mxb-field">
        <label for="mxb-rule-json">{{ t('mxbackup_json_path') }}</label>
        <InputText id="mxb-rule-json" v-model="form.json_path" autocomplete="off" />
        <small>{{ t('mxbackup_json_path_desc') }}</small>
      </div>

      <div v-if="needsValue" class="mxb-field">
        <label for="mxb-rule-value">{{ t('mxbackup_replacement') }}</label>
        <Textarea id="mxb-rule-value" v-model="form.value" rows="2" auto-resize />
        <small>{{ t('mxbackup_replacement_desc') }}</small>
      </div>

      <div class="mxb-field mxb-field--half">
        <label for="mxb-rule-priority">{{ t('mxbackup_priority') }}</label>
        <InputNumber
            input-id="mxb-rule-priority" v-model="form.priority" :min="0" :max="9999" show-buttons fluid />
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
