<script setup>
import { onMounted, ref } from 'vue';
import { Button, Select, Tab, TabList, TabPanel, TabPanels, Tabs, Toast, ConfirmDialog, useToast } from 'primevue';
import { t } from './utils/i18n.js';
import { config, errorMessage } from './api/connector.js';
import { ConfigApi, RunApi } from './api/connector.js';
import { useProfiles } from './composables/useProfiles.js';
import ProfilesPage from './pages/ProfilesPage.vue';
import TablesPage from './pages/TablesPage.vue';
import MaskingPage from './pages/MaskingPage.vue';
import HistoryPage from './pages/HistoryPage.vue';
import SettingsPage from './pages/SettingsPage.vue';
import RunReport from './components/RunReport.vue';

const toast = useToast();
const cfg = config();
const can = cfg.permissions || {};
const { profiles, load: loadProfiles, defaultProfile } = useProfiles();

const runProfile = ref('');
const running = ref(false);
const report = ref(null);
const historyRef = ref(null);
const activeTab = ref('profiles');

onMounted(async () => {
  await loadProfiles();
  runProfile.value = defaultProfile();
});

async function run(dryRun) {
  if (!runProfile.value || running.value) return;
  running.value = true;
  report.value = null;
  try {
    const res = await RunApi.create(runProfile.value, dryRun);
    report.value = { dryRun, data: res.object?.report || null, archive: res.object?.archive || '' };
    toast.add({
      severity: 'success',
      summary: t('mxbackup_run'),
      detail: res.message || t('mxbackup_finished'),
      life: 6000,
    });
    historyRef.value?.reload();
  } catch (e) {
    toast.add({
      severity: 'error',
      summary: t('mxbackup_error'),
      detail: errorMessage(e, t('mxbackup_error')),
      life: 10000,
    });
  } finally {
    running.value = false;
  }
}

// Проверка конфигурации была доступна только из CLI — процессор существовал,
// но UI его не звал. Здесь она рядом с запуском, где и нужна.
async function validate() {
  if (!runProfile.value) return;
  try {
    const res = await ConfigApi.validate(runProfile.value);
    toast.add({
      severity: 'success',
      summary: t('mxbackup_validate_config'),
      detail: res.message || t('mxbackup_config_valid'),
      life: 6000,
    });
  } catch (e) {
    toast.add({
      severity: 'warn',
      summary: t('mxbackup_validate_config'),
      detail: errorMessage(e, t('mxbackup_error')),
      life: 12000,
    });
  }
}
</script>

<template>
  <div class="mxb">
    <Toast position="top-right" />
    <ConfirmDialog />

    <header class="mxb-head">
      <div class="mxb-head__title">
        <h1>{{ t('mxbackup') }}</h1>
        <p>{{ t('mxbackup_intro') }}</p>
      </div>

      <div class="mxb-launch" role="group" :aria-label="t('mxbackup_run')">
        <Select
          v-model="runProfile"
          :options="profiles"
          option-label="name"
          option-value="name"
          :placeholder="t('mxbackup_run_profile')"
          :aria-label="t('mxbackup_run_profile')"
          class="mxb-launch__profile"
        />
        <Button
          :label="t('mxbackup_create_backup')"
          icon="pi pi-download"
          :loading="running"
          :disabled="!can.run || !runProfile"
          @click="run(false)"
        />
        <Button
          :label="t('mxbackup_dryrun')"
          icon="pi pi-search"
          severity="secondary"
          outlined
          :disabled="running || !can.run || !runProfile"
          @click="run(true)"
        />
        <Button
          :label="t('mxbackup_validate_config')"
          icon="pi pi-check-circle"
          severity="secondary"
          text
          :disabled="running || !runProfile"
          @click="validate"
        />
      </div>
    </header>

    <RunReport
      v-if="report"
      :report="report.data"
      :archive="report.archive"
      :dry-run="report.dryRun"
      class="mxb-lastrun"
      @close="report = null"
    />

    <Tabs v-model:value="activeTab" class="mxb-tabs">
      <TabList>
        <Tab value="profiles">{{ t('mxbackup_profiles') }}</Tab>
        <Tab value="tables">{{ t('mxbackup_tables') }}</Tab>
        <Tab value="masking">{{ t('mxbackup_masking') }}</Tab>
        <Tab value="history">{{ t('mxbackup_history') }}</Tab>
        <Tab value="settings">{{ t('mxbackup_settings') }}</Tab>
      </TabList>
      <TabPanels>
        <TabPanel value="profiles"><ProfilesPage /></TabPanel>
        <TabPanel value="tables"><TablesPage /></TabPanel>
        <TabPanel value="masking"><MaskingPage /></TabPanel>
        <TabPanel value="history"><HistoryPage ref="historyRef" /></TabPanel>
        <TabPanel value="settings"><SettingsPage /></TabPanel>
      </TabPanels>
    </Tabs>
  </div>
</template>
