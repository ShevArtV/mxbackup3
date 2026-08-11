<script setup>
import { computed, ref, watch } from 'vue';
import { Button, Column, DataTable, Dialog, InputNumber, InputText, Message, Select, Textarea, useToast } from 'primevue';
import { t } from '../utils/i18n.js';
import { RuleApi, errorMessage } from '../api/connector.js';

const props = defineProps({
  visible: { type: Boolean, default: false },
  row: { type: Object, default: null },
  profile: { type: String, default: '' },
  table: { type: String, default: '' },
  columns: { type: Array, default: () => [] },
});
const emit = defineEmits(['update:visible', 'saved']);

const toast = useToast();
const saving = ref(false);
const error = ref('');

const form = ref({ action: 'mask', json_path: '', value: '', priority: 100, when: [] });

const actions = computed(() => [
  { value: 'mask', label: t('mxbackup_action_mask') },
  { value: 'hide', label: t('mxbackup_action_hide') },
  { value: 'hash', label: t('mxbackup_action_hash') },
  { value: 'replace', label: t('mxbackup_action_replace') },
]);

const isCustom = computed(() => props.row?.source === 'custom' && props.row?.rule_id);
const needsValue = computed(() => form.value.action === 'replace');

// Хранится условие как `{колонка: [маска, маска]}`, а правится построчно: одна
// строка таблицы — одна пара «колонка + маска». Повторы колонки не запрещены,
// они и означают «ИЛИ»; при сохранении строки схлопываются обратно в массивы.
function conditionRows(value) {
  if (!value || typeof value !== 'object' || Array.isArray(value)) return [];
  return Object.entries(value).flatMap(([column, patterns]) =>
    (Array.isArray(patterns) ? patterns : [patterns]).map((pattern) => ({
      column,
      pattern: String(pattern),
      invalidColumn: false,
      invalidPattern: false,
    }))
  );
}

function addCondition() {
  form.value.when.push({ column: '', pattern: '', invalidColumn: false, invalidPattern: false });
}

function removeCondition(index) {
  form.value.when.splice(index, 1);
}

// DataTable в режиме cell редактирует копию строки, значение надо перенести самим.
function onCellEditComplete(event) {
  const { data, field, newValue } = event;
  data[field] = String(newValue === null || newValue === undefined ? '' : newValue).trim();
  data[field === 'column' ? 'invalidColumn' : 'invalidPattern'] = false;
}

function normalizedWhen() {
  const result = {};
  let invalid = false;
  // Полностью пустая строка — след кнопки «Добавить условие», её просто убираем.
  form.value.when = form.value.when.filter(
    (condition) => String(condition.column || '').trim() || String(condition.pattern || '').trim()
  );
  for (const condition of form.value.when) {
    const column = String(condition.column || '').trim();
    const pattern = String(condition.pattern || '').trim();
    condition.invalidColumn = !column;
    condition.invalidPattern = !pattern || pattern === '!';
    if (condition.invalidColumn || condition.invalidPattern) {
      invalid = true;
      continue;
    }
    if (!Object.hasOwn(result, column)) result[column] = [];
    if (!result[column].includes(pattern)) result[column].push(pattern);
  }
  if (invalid) throw new Error(t('mxbackup_invalid_when'));
  return result;
}

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
      when: conditionRows(props.row.when),
    };
  },
  { immediate: true }
);

async function save() {
  saving.value = true;
  error.value = '';
  // JSON-путь дописывается к цели третьим сегментом: table.column.path.
  const jsonPath = String(form.value.json_path || '').replace(/^\$?\.?/, '');
  try {
    const params = {
      profile_id: props.profile,
      target_type: jsonPath ? 'json_path' : 'column',
      target: `${props.table}.${props.row.column}${jsonPath ? '.' + jsonPath : ''}`,
      // Не `action`: в коннекторе MODX этот параметр занят именем процессора.
      rule_action: form.value.action,
      value: form.value.value,
      when: JSON.stringify(normalizedWhen()),
      priority: form.value.priority,
      active: 1,
    };
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

      <fieldset class="mxb-fieldset mxb-conditions">
        <legend>{{ t('mxbackup_when_title') }}</legend>
        <small class="mxb-field__hint">{{ t('mxbackup_when_desc') }}</small>

        <DataTable
          :value="form.when"
          edit-mode="cell"
          size="small"
          class="mxb-when-table"
          @cell-edit-complete="onCellEditComplete"
        >
          <template #empty>
            <span class="mxb-when__empty">{{ t('mxbackup_when_empty') }}</span>
          </template>

          <Column :header="t('mxbackup_when_column')" field="column" style="width: 40%">
            <template #body="{ data }">
              <span v-if="data.column" :class="{ 'mxb-when__invalid': data.invalidColumn }">{{ data.column }}</span>
              <span v-else class="mxb-when__placeholder" :class="{ 'mxb-when__invalid': data.invalidColumn }">
                {{ t('mxbackup_when_column_empty') }}
              </span>
            </template>
            <template #editor="{ data }">
              <Select v-model="data.column" :options="columns" filter fluid />
            </template>
          </Column>

          <Column :header="t('mxbackup_when_pattern')" field="pattern">
            <template #body="{ data }">
              <code v-if="data.pattern" :class="{ 'mxb-when__invalid': data.invalidPattern }">{{ data.pattern }}</code>
              <span v-else class="mxb-when__placeholder" :class="{ 'mxb-when__invalid': data.invalidPattern }">
                {{ t('mxbackup_when_example') }}
              </span>
            </template>
            <template #editor="{ data }">
              <InputText v-model="data.pattern" :placeholder="t('mxbackup_when_example')" fluid autocomplete="off" />
            </template>
          </Column>

          <Column style="width: 3rem">
            <template #body="{ index }">
              <Button
                icon="pi pi-times"
                severity="danger"
                text
                rounded
                :aria-label="t('mxbackup_remove')"
                @click="removeCondition(index)"
              />
            </template>
          </Column>
        </DataTable>

        <Button
          :label="t('mxbackup_add_condition')"
          icon="pi pi-plus"
          severity="secondary"
          size="small"
          outlined
          class="mxb-condition__add"
          @click="addCondition"
        />

        <small class="mxb-field__hint mxb-when__legend">{{ t('mxbackup_when_hint') }}</small>
      </fieldset>

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
