<script setup>
import { computed } from 'vue';
import { Button, Message, Tag } from 'primevue';
import { t } from '../utils/i18n.js';

const props = defineProps({
  report: { type: Object, default: null },
  archive: { type: String, default: '' },
  dryRun: { type: Boolean, default: false },
  // Внутри диалога закрывать нечего — там за это отвечает сам диалог.
  closable: { type: Boolean, default: true },
});
defineEmits(['close']);

const stats = computed(() => props.report?.stats || {});
const tables = computed(() => stats.value.table_names || []);
const truncated = computed(() => stats.value.truncated_tables || []);
const warnings = computed(() => props.report?.warnings || []);
const errors = computed(() => props.report?.errors || []);

const figures = computed(() => [
  { key: 'files', label: t('mxbackup_report_files'), value: stats.value.files || 0 },
  { key: 'tables', label: t('mxbackup_report_tables'), value: stats.value.tables || 0 },
  { key: 'masked', label: t('mxbackup_report_masked_columns'), value: stats.value.masked_columns || 0 },
  { key: 'truncated', label: t('mxbackup_report_truncated'), value: truncated.value.length },
]);
</script>

<template>
  <section class="mxb-report">
    <header class="mxb-report__head">
      <h2>
        {{ dryRun ? t('mxbackup_run_type_dry') : t('mxbackup_run_type_backup') }}
        <Tag v-if="dryRun" :value="t('mxbackup_no_archive_dry')" severity="secondary" />
      </h2>
      <Button
        v-if="closable"
        icon="pi pi-times"
        severity="secondary"
        text
        rounded
        :aria-label="t('mxbackup_close')"
        @click="$emit('close')"
      />
    </header>

    <dl class="mxb-figures">
      <div v-for="figure in figures" :key="figure.key">
        <dt>{{ figure.label }}</dt>
        <dd>{{ figure.value }}</dd>
      </div>
    </dl>

    <p v-if="archive" class="mxb-report__archive">
      <span>{{ t('mxbackup_archive') }}:</span> <code>{{ archive }}</code>
    </p>

    <p v-if="tables.length" class="mxb-report__list">
      <span>{{ t('mxbackup_report_table_list') }}:</span> {{ tables.join(', ') }}
    </p>
    <p v-if="truncated.length" class="mxb-report__list">
      <span>{{ t('mxbackup_report_truncated_list') }}:</span> {{ truncated.join(', ') }}
    </p>

    <Message v-for="(warning, index) in warnings" :key="'w' + index" severity="warn" :closable="false">
      {{ warning }}
    </Message>
    <Message v-for="(error, index) in errors" :key="'e' + index" severity="error" :closable="false">
      {{ error }}
    </Message>
  </section>
</template>
