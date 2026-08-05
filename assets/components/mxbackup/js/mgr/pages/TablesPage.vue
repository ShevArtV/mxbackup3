<script setup>
import { onMounted, ref, watch } from 'vue';
import {
  Button, Column, DataTable, IconField, InputIcon, InputText, Select, ToggleSwitch, useToast,
} from 'primevue';
import { t } from '../utils/i18n.js';
import { formatBytes } from '../utils/format.js';
import { TableApi, config, errorMessage, listOf } from '../api/connector.js';
import { useProfiles } from '../composables/useProfiles.js';

const toast = useToast();
const can = config().permissions || {};
const { profiles, load: loadProfiles, defaultProfile } = useProfiles();

const profile = ref('');
const rows = ref([]);
const total = ref(0);
const loading = ref(false);
const first = ref(0);
const limit = ref(25);
const query = ref('');
const selectionMode = ref('all_except');

// Режим относится к таблицам, которые появятся в базе позже: включать их в
// архив автоматически или только явно выбранные.
const modes = () => [
  { value: 'all_except', label: t('mxbackup_table_mode_all_except') },
  { value: 'selected', label: t('mxbackup_table_mode_selected') },
];

onMounted(async () => {
  try {
    await loadProfiles();
    profile.value = defaultProfile();
  } catch (e) {
    toast.add({ severity: 'error', summary: t('mxbackup_error'), detail: errorMessage(e), life: 12000 });
  }
});

watch(profile, () => {
  first.value = 0;
  load();
});

let searchTimer = null;
watch(query, () => {
  clearTimeout(searchTimer);
  searchTimer = setTimeout(() => {
    first.value = 0;
    load();
  }, 250);
});

async function load() {
  if (!profile.value) return;
  loading.value = true;
  try {
    const res = await TableApi.getList({
      profile_id: profile.value,
      query: query.value,
      start: first.value,
      limit: limit.value,
    });
    rows.value = listOf(res);
    total.value = res.total ?? rows.value.length;
    if (rows.value[0]?.selection_mode) selectionMode.value = rows.value[0].selection_mode;
  } catch (e) {
    rows.value = [];
    total.value = 0;
    toast.add({ severity: 'error', summary: t('mxbackup_error'), detail: errorMessage(e), life: 10000 });
  } finally {
    loading.value = false;
  }
}

function onPage(event) {
  first.value = event.first;
  limit.value = event.rows;
  load();
}

async function toggle(row) {
  const included = !row.included;
  try {
    await TableApi.update({
      profile_id: profile.value,
      operation: 'toggle',
      table: row.name,
      included: included ? 1 : 0,
    });
    row.included = included;
  } catch (e) {
    toast.add({ severity: 'error', summary: t('mxbackup_error'), detail: errorMessage(e), life: 10000 });
  }
}

async function setAll(included) {
  try {
    const res = await TableApi.update({
      profile_id: profile.value,
      operation: 'set_all',
      included: included ? 1 : 0,
      query: query.value,
    });
    toast.add({
      severity: 'success',
      summary: t('mxbackup_tables'),
      detail: res.message || t('mxbackup_tables_saved'),
      life: 5000,
    });
    await load();
  } catch (e) {
    toast.add({ severity: 'error', summary: t('mxbackup_error'), detail: errorMessage(e), life: 10000 });
  }
}

async function saveMode() {
  try {
    const res = await TableApi.update({
      profile_id: profile.value,
      operation: 'mode',
      selection_mode: selectionMode.value,
    });
    toast.add({
      severity: 'success',
      summary: t('mxbackup_tables'),
      detail: res.message || t('mxbackup_tables_saved'),
      life: 5000,
    });
    await load();
  } catch (e) {
    toast.add({ severity: 'error', summary: t('mxbackup_error'), detail: errorMessage(e), life: 10000 });
  }
}
</script>

<template>
  <div class="mxb-page">
    <div class="mxb-filters">
      <div class="mxb-field mxb-field--inline">
        <label for="mxb-tables-profile">{{ t('mxbackup_profile') }}</label>
        <Select
          id="mxb-tables-profile"
          v-model="profile"
          :options="profiles"
          option-label="name"
          option-value="name"
        />
      </div>

      <div class="mxb-field mxb-field--inline">
        <label for="mxb-tables-mode">{{ t('mxbackup_selection_mode') }}</label>
        <Select
          id="mxb-tables-mode"
          v-model="selectionMode"
          :options="modes()"
          option-label="label"
          option-value="value"
          :disabled="!can.manage"
          @change="saveMode"
        />
      </div>

      <IconField class="mxb-search">
        <InputIcon class="pi pi-search" />
        <InputText v-model="query" :placeholder="t('mxbackup_search_table')" :aria-label="t('mxbackup_search_table')" />
      </IconField>

      <div class="mxb-filters__actions">
        <Button
          :label="t('mxbackup_include_all')"
          size="small"
          severity="secondary"
          outlined
          :disabled="!can.manage || loading"
          @click="setAll(true)"
        />
        <Button
          :label="t('mxbackup_exclude_all')"
          size="small"
          severity="secondary"
          outlined
          :disabled="!can.manage || loading"
          @click="setAll(false)"
        />
      </div>
    </div>

    <p class="mxb-hint">{{ query ? t('mxbackup_bulk_scope_filtered') : t('mxbackup_bulk_scope_all') }}</p>

    <DataTable
      :value="rows"
      :loading="loading"
      data-key="name"
      size="small"
      striped-rows
      lazy
      paginator
      :rows="limit"
      :first="first"
      :total-records="total"
      :rows-per-page-options="[25, 50, 100]"
      class="mxb-table"
      @page="onPage"
    >
      <template #empty>
        <p class="mxb-empty">{{ query ? t('mxbackup_tables_empty_search') : t('mxbackup_tables_empty') }}</p>
      </template>

      <Column :header="t('mxbackup_in_archive')" style="width: 9rem">
        <template #body="{ data }">
          <ToggleSwitch
            :model-value="data.included"
            :disabled="!can.manage"
            :aria-label="data.name"
            @update:model-value="toggle(data)"
          />
        </template>
      </Column>
      <Column field="name" :header="t('mxbackup_table')" />
      <Column field="rows" :header="t('mxbackup_rows')" style="width: 9rem">
        <template #body="{ data }">{{ (data.rows || 0).toLocaleString() }}</template>
      </Column>
      <Column field="size" :header="t('mxbackup_size')" style="width: 9rem">
        <template #body="{ data }">{{ formatBytes(data.size) }}</template>
      </Column>
      <Column field="engine" :header="t('mxbackup_engine')" style="width: 9rem" />
    </DataTable>
  </div>
</template>
