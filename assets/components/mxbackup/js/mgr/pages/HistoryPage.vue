<script setup>
import { onMounted, ref } from 'vue';
import { Button, Column, DataTable, Tag, useConfirm, useToast } from 'primevue';
import { t } from '../utils/i18n.js';
import { formatBytes, formatDate, formatDuration, statusSeverity } from '../utils/format.js';
import { RunApi, config, errorMessage, listOf } from '../api/connector.js';
import RunDetailsDialog from '../components/RunDetailsDialog.vue';
import RestoreDialog from '../components/RestoreDialog.vue';

const toast = useToast();
const confirm = useConfirm();
const can = config().permissions || {};

const rows = ref([]);
const total = ref(0);
const loading = ref(false);
const first = ref(0);
const limit = ref(30);
const details = ref({ open: false, run: null });
const restore = ref({ open: false, run: null });

onMounted(() => load());

async function load() {
  loading.value = true;
  try {
    const res = await RunApi.getList({
      start: first.value,
      limit: limit.value,
      sort: 'startedon',
      dir: 'DESC',
    });
    rows.value = listOf(res);
    total.value = res.total ?? rows.value.length;
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

const runTypeLabel = (type) => t('mxbackup_run_type_' + ({ dry_run: 'dry', restore: 'restore' }[type] || 'backup'));

// Восстанавливать можно только из успешного архива обычного backup: у dry-run
// архива нет, у неудачного запуска он неполон.
const restorable = (row) =>
  can.restore
  && row.run_type === 'backup'
  && row.archive_path
  && ['success', 'warning'].includes(row.status);

function statusLabel(row) {
  const label = t('mxbackup_status_' + row.status);
  const count = row.errors_count || row.warnings_count;
  return count ? `${label} (${count})` : label;
}

function cancelRun(row) {
  confirm.require({
    header: t('mxbackup_stop_backup'),
    message: t('mxbackup_confirm_stop'),
    accept: async () => {
      try {
        const res = await RunApi.cancel(row.id);
        toast.add({ severity: 'warn', summary: t('mxbackup_stop_backup'), detail: res.message || t('mxbackup_stop_requested'), life: 6000 });
        await load();
      } catch (e) {
        toast.add({ severity: 'error', summary: t('mxbackup_error'), detail: errorMessage(e), life: 8000 });
      }
    },
  });
}

defineExpose({ reload: () => { first.value = 0; return load(); } });
</script>

<template>
  <div class="mxb-page">
    <DataTable
      :value="rows"
      :loading="loading"
      data-key="id"
      size="small"
      striped-rows
      lazy
      paginator
      :rows="limit"
      :first="first"
      :total-records="total"
      :rows-per-page-options="[30, 60, 100]"
      class="mxb-table"
      @page="onPage"
      @row-dblclick="details = { open: true, run: $event.data }"
    >
      <template #header>
        <div class="mxb-toolbar">
          <p class="mxb-toolbar__hint">{{ t('mxbackup_history_hint') }}</p>
          <Button
            :label="t('mxbackup_refresh')"
            icon="pi pi-refresh"
            size="small"
            severity="secondary"
            outlined
            :loading="loading"
            @click="load"
          />
        </div>
      </template>

      <template #empty>
        <p class="mxb-empty">{{ t('mxbackup_history_empty') }}</p>
      </template>

      <Column field="id" :header="t('mxbackup_id')" style="width: 5rem" />
      <Column :header="t('mxbackup_run_type')" style="width: 12rem">
        <template #body="{ data }">{{ runTypeLabel(data.run_type) }}</template>
      </Column>
      <Column field="profile" :header="t('mxbackup_profile')" style="width: 9rem" />
      <Column :header="t('mxbackup_status')" style="width: 12rem">
        <template #body="{ data }">
          <Tag :value="statusLabel(data)" :severity="statusSeverity(data.status)" />
        </template>
      </Column>
      <Column :header="t('mxbackup_archive')">
        <template #body="{ data }">
          <span v-if="data.run_type === 'dry_run'" class="mxb-muted">{{ t('mxbackup_no_archive_dry') }}</span>
          <span v-else-if="data.archive_name" :title="data.archive_path">{{ data.archive_name }}</span>
          <span v-else class="mxb-muted">—</span>
        </template>
      </Column>
      <Column :header="t('mxbackup_size')" style="width: 8rem">
        <template #body="{ data }">
          {{ data.run_type === 'dry_run' ? '—' : formatBytes(data.archive_size) }}
        </template>
      </Column>
      <Column :header="t('mxbackup_started')" style="width: 12rem">
        <template #body="{ data }">{{ formatDate(data.startedon) }}</template>
      </Column>
      <Column :header="t('mxbackup_duration')" style="width: 8rem">
        <template #body="{ data }">{{ formatDuration(data.duration) }}</template>
      </Column>
      <Column style="width: 6rem">
        <template #body="{ data }">
          <div class="mxb-rowactions">
            <Button
              icon="pi pi-list"
              severity="secondary"
              text
              rounded
              size="small"
              v-tooltip.top="t('mxbackup_run_details')"
              :aria-label="t('mxbackup_run_details')"
              @click="details = { open: true, run: data }"
            />
            <Button
              v-if="data.status === 'running' && can.run"
              icon="pi pi-times"
              severity="danger"
              text
              rounded
              size="small"
              v-tooltip.top="t('mxbackup_stop_backup')"
              :aria-label="t('mxbackup_stop_backup')"
              @click="cancelRun(data)"
            />
            <Button
              v-if="restorable(data)"
              icon="pi pi-replay"
              severity="warn"
              text
              rounded
              size="small"
              v-tooltip.top="t('mxbackup_restore')"
              :aria-label="t('mxbackup_restore')"
              @click="restore = { open: true, run: data }"
            />
          </div>
        </template>
      </Column>
    </DataTable>

    <RunDetailsDialog v-model:visible="details.open" :run="details.run" />
    <RestoreDialog v-model:visible="restore.open" :run="restore.run" @restored="load" />
  </div>
</template>
