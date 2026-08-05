<script setup>
import { computed } from 'vue';
import { Button, Dialog, Tag } from 'primevue';
import { t } from '../utils/i18n.js';
import { formatDate, formatDuration, statusSeverity } from '../utils/format.js';
import RunReport from './RunReport.vue';

const props = defineProps({
  visible: { type: Boolean, default: false },
  run: { type: Object, default: null },
});
const emit = defineEmits(['update:visible']);

const report = computed(() => props.run?.report_json || {});
const facts = computed(() => {
  const run = props.run;
  if (!run) return [];
  const list = [
    { key: 'profile', label: t('mxbackup_profile'), value: run.profile },
    { key: 'started', label: t('mxbackup_started'), value: formatDate(run.startedon) },
    { key: 'completed', label: t('mxbackup_completed'), value: formatDate(run.completedon) },
    { key: 'duration', label: t('mxbackup_duration'), value: formatDuration(run.duration) },
  ];
  if (run.archive_path) list.push({ key: 'archive', label: t('mxbackup_archive'), value: run.archive_path, mono: true });
  if (run.archive_checksum) list.push({ key: 'sum', label: 'SHA-256', value: run.archive_checksum, mono: true });
  return list;
});
</script>

<template>
  <Dialog
    :visible="visible"
    modal
    :header="run ? `${t('mxbackup_run_details')} #${run.id}` : t('mxbackup_run_details')"
    :style="{ width: '52rem' }"
    :breakpoints="{ '54rem': '95vw' }"
    @update:visible="emit('update:visible', $event)"
  >
    <div v-if="run" class="mxb-details">
      <Tag :value="t('mxbackup_status_' + run.status)" :severity="statusSeverity(run.status)" />

      <dl class="mxb-facts">
        <template v-for="fact in facts" :key="fact.key">
          <dt>{{ fact.label }}</dt>
          <dd :class="{ 'mxb-mono': fact.mono }">{{ fact.value }}</dd>
        </template>
      </dl>

      <RunReport
        :report="report"
        :archive="''"
        :dry-run="run.run_type === 'dry_run'"
        :closable="false"
        class="mxb-details__report"
      />
    </div>

    <template #footer>
      <Button :label="t('mxbackup_close')" severity="secondary" outlined @click="emit('update:visible', false)" />
    </template>
  </Dialog>
</template>
