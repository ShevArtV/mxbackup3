<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { Button, Column, DataTable, Select, Tag, useConfirm, useToast } from 'primevue';
import { t } from '../utils/i18n.js';
import { actionSeverity } from '../utils/format.js';
import { ColumnApi, RuleApi, TableApi, config, errorMessage, listOf } from '../api/connector.js';
import { useProfiles } from '../composables/useProfiles.js';
import RuleDialog from '../components/RuleDialog.vue';

const toast = useToast();
const confirm = useConfirm();
const can = config().permissions || {};
const { profiles, load: loadProfiles, defaultProfile } = useProfiles();

const profile = ref('');
const table = ref('');
const tables = ref([]);
const rows = ref([]);
const loading = ref(false);
const dialog = ref({ open: false, row: null });

// Целая таблица очищается отдельным правилом; в списке колонок оно приходит
// строкой column === '*'.
const truncateRow = computed(() => rows.value.find((r) => r.column === '*') || null);

onMounted(async () => {
  await loadProfiles();
  // Обезличивание осмысленно прежде всего для dev-профиля — открываем на нём.
  profile.value = defaultProfile('dev');
});

watch(profile, async () => {
  rows.value = [];
  table.value = '';
  await loadTables();
});

watch(table, () => loadColumns());

async function loadTables() {
  if (!profile.value) return;
  try {
    // limit=0 — нужен весь список включённых таблиц, это выпадающий список.
    const res = await TableApi.getList({ profile_id: profile.value, included_only: 1, limit: 0 });
    tables.value = listOf(res).map((row) => row.name);
    // Таблица пользователей — самый частый повод сюда зайти.
    const users = tables.value.find((name) => /(^|_)users$/.test(name) && !/(^|_)active_users$/.test(name));
    table.value = users || tables.value[0] || '';
  } catch (e) {
    tables.value = [];
    toast.add({ severity: 'error', summary: t('mxbackup_error'), detail: errorMessage(e), life: 10000 });
  }
}

async function loadColumns() {
  if (!profile.value || !table.value) {
    rows.value = [];
    return;
  }
  loading.value = true;
  try {
    const res = await ColumnApi.getList({ profile_id: profile.value, table: table.value });
    rows.value = listOf(res);
  } catch (e) {
    rows.value = [];
    toast.add({ severity: 'error', summary: t('mxbackup_error'), detail: errorMessage(e), life: 10000 });
  } finally {
    loading.value = false;
  }
}

function editRule(row) {
  if (!can.manage || row.column === '*') return;
  dialog.value = { open: true, row: { ...row } };
}

function truncateTable() {
  if (truncateRow.value) {
    toast.add({
      severity: 'info',
      summary: t('mxbackup_masking'),
      detail: truncateRow.value.source === 'standard'
        ? t('mxbackup_standard_truncate_exists')
        : t('mxbackup_custom_truncate_exists'),
      life: 8000,
    });
    return;
  }
  confirm.require({
    header: t('mxbackup_truncate_table'),
    message: t('mxbackup_confirm_truncate'),
    icon: 'pi pi-exclamation-triangle',
    acceptProps: { label: t('mxbackup_action_truncate'), severity: 'warn' },
    rejectProps: { label: t('mxbackup_cancel'), severity: 'secondary', outlined: true },
    accept: async () => {
      try {
        await RuleApi.create({
          profile_id: profile.value,
          target_type: 'table',
          target: table.value,
          rule_action: 'truncate',
          priority: 100,
          active: 1,
        });
        await loadColumns();
      } catch (e) {
        toast.add({ severity: 'error', summary: t('mxbackup_error'), detail: errorMessage(e), life: 10000 });
      }
    },
  });
}

function removeRule(row) {
  confirm.require({
    header: t('mxbackup_remove'),
    message: t('mxbackup_confirm_remove_rule'),
    icon: 'pi pi-exclamation-triangle',
    acceptProps: { label: t('mxbackup_remove'), severity: 'danger' },
    rejectProps: { label: t('mxbackup_cancel'), severity: 'secondary', outlined: true },
    accept: async () => {
      try {
        await RuleApi.remove(profile.value, row.rule_id);
        await loadColumns();
      } catch (e) {
        toast.add({ severity: 'error', summary: t('mxbackup_error'), detail: errorMessage(e), life: 10000 });
      }
    },
  });
}

async function saved() {
  dialog.value = { open: false, row: null };
  await loadColumns();
}

const sourceLabel = (source) => t('mxbackup_source_' + (source || 'none'));
const sourceSeverity = (source) => ({ standard: 'success', custom: 'info' }[source] || 'secondary');
</script>

<template>
  <div class="mxb-page">
    <div class="mxb-filters">
      <div class="mxb-field mxb-field--inline">
        <label for="mxb-mask-profile">{{ t('mxbackup_profile') }}</label>
        <Select
          id="mxb-mask-profile"
          v-model="profile"
          :options="profiles"
          option-label="name"
          option-value="name"
        />
      </div>

      <div class="mxb-field mxb-field--inline">
        <label for="mxb-mask-table">{{ t('mxbackup_table') }}</label>
        <Select
          id="mxb-mask-table"
          v-model="table"
          :options="tables"
          filter
          :placeholder="t('mxbackup_select_table')"
          class="mxb-select--wide"
        />
      </div>

      <div class="mxb-filters__actions">
        <Button
          :label="t('mxbackup_truncate_table')"
          icon="pi pi-eraser"
          size="small"
          severity="secondary"
          outlined
          :disabled="!can.manage || !table"
          @click="truncateTable"
        />
      </div>
    </div>

    <DataTable
      :value="rows"
      :loading="loading"
      data-key="column"
      size="small"
      striped-rows
      class="mxb-table"
      @row-dblclick="editRule($event.data)"
    >
      <template #empty>
        <p class="mxb-empty">{{ table ? t('mxbackup_columns_empty') : t('mxbackup_select_table') }}</p>
      </template>

      <Column field="column" :header="t('mxbackup_column')" style="width: 18rem">
        <template #body="{ data }">
          <span v-if="data.column === '*'" class="mxb-strong">{{ t('mxbackup_whole_table') }}</span>
          <span v-else>{{ data.column }}</span>
        </template>
      </Column>
      <Column field="type" :header="t('mxbackup_sql_type')" style="width: 14rem" />
      <Column :header="t('mxbackup_masking_action')" style="width: 16rem">
        <template #body="{ data }">
          <Tag
            v-if="data.action"
            :value="t('mxbackup_action_' + data.action)"
            :severity="actionSeverity(data.action)"
          />
          <span v-else class="mxb-muted">{{ t('mxbackup_action_none') }}</span>
        </template>
      </Column>
      <Column :header="t('mxbackup_rule_source')" style="width: 12rem">
        <template #body="{ data }">
          <Tag :value="sourceLabel(data.source)" :severity="sourceSeverity(data.source)" />
        </template>
      </Column>
      <Column field="value" :header="t('mxbackup_value')">
        <template #body="{ data }">
          <code v-if="data.value !== null && data.value !== ''">{{ data.value }}</code>
          <span v-else class="mxb-muted">—</span>
        </template>
      </Column>
      <Column v-if="can.manage" style="width: 6rem">
        <template #body="{ data }">
          <div class="mxb-rowactions">
            <Button
              v-if="data.column !== '*'"
              icon="pi pi-pencil"
              severity="secondary"
              text
              rounded
              size="small"
              v-tooltip.top="data.source === 'custom' ? t('mxbackup_edit_rule') : t('mxbackup_add_rule')"
              :aria-label="data.source === 'custom' ? t('mxbackup_edit_rule') : t('mxbackup_add_rule')"
              @click="editRule(data)"
            />
            <Button
              v-if="data.source === 'custom' && data.rule_id"
              icon="pi pi-trash"
              severity="danger"
              text
              rounded
              size="small"
              v-tooltip.top="t('mxbackup_remove')"
              :aria-label="t('mxbackup_remove')"
              @click="removeRule(data)"
            />
          </div>
        </template>
      </Column>
    </DataTable>

    <RuleDialog
      v-model:visible="dialog.open"
      :row="dialog.row"
      :profile="profile"
      :table="table"
      @saved="saved"
    />
  </div>
</template>
