<script setup>
import { onMounted, ref } from 'vue';
import { Button, Column, DataTable, useConfirm, useToast } from 'primevue';
import { t } from '../utils/i18n.js';
import { formatDate } from '../utils/format.js';
import { ProfileApi, config, errorMessage } from '../api/connector.js';
import { useProfiles } from '../composables/useProfiles.js';
import ProfileDialog from '../components/ProfileDialog.vue';
import BoolMark from '../components/BoolMark.vue';

const toast = useToast();
const confirm = useConfirm();
const can = config().permissions || {};
const { profiles, loading, load } = useProfiles();

const dialog = ref({ open: false, profile: null });

onMounted(() => reload());

async function reload(force = false) {
  try {
    await load(force);
  } catch (e) {
    toast.add({ severity: 'error', summary: t('mxbackup_error'), detail: errorMessage(e), life: 12000 });
  }
}

function add() {
  dialog.value = { open: true, profile: null };
}

function edit(row) {
  if (!can.manage) return;
  dialog.value = { open: true, profile: { ...row } };
}

function remove(row) {
  confirm.require({
    header: t('mxbackup_remove_profile'),
    message: t('mxbackup_confirm_remove_profile', { name: row.name }),
    icon: 'pi pi-exclamation-triangle',
    acceptProps: { label: t('mxbackup_remove'), severity: 'danger' },
    rejectProps: { label: t('mxbackup_cancel'), severity: 'secondary', outlined: true },
    accept: async () => {
      try {
        await ProfileApi.remove(row.id);
        toast.add({ severity: 'success', summary: t('mxbackup_profiles'), detail: t('mxbackup_profile_removed'), life: 5000 });
        await reload(true);
      } catch (e) {
        toast.add({ severity: 'error', summary: t('mxbackup_error'), detail: errorMessage(e), life: 10000 });
      }
    },
  });
}

async function saved() {
  dialog.value = { open: false, profile: null };
  await reload(true);
}

const builtin = (row) => row.name === 'prod' || row.name === 'dev';
</script>

<template>
  <div class="mxb-page">
    <DataTable
      :value="profiles"
      :loading="loading"
      data-key="id"
      size="small"
      striped-rows
      class="mxb-table"
      @row-dblclick="edit($event.data)"
    >
      <template #header>
        <div class="mxb-toolbar">
          <p class="mxb-toolbar__hint">{{ t('mxbackup_profiles_hint') }}</p>
          <Button
            v-if="can.manage"
            :label="t('mxbackup_add_profile')"
            icon="pi pi-plus"
            size="small"
            @click="add"
          />
        </div>
      </template>

      <template #empty>
        <p class="mxb-empty">{{ t('mxbackup_profiles_empty') }}</p>
      </template>

      <Column field="name" :header="t('mxbackup_name')" style="width: 14rem">
        <template #body="{ data }">
          <span class="mxb-strong">{{ data.name }}</span>
        </template>
      </Column>
      <Column field="mode" :header="t('mxbackup_mode')" style="width: 10rem">
        <template #body="{ data }">{{ t('mxbackup_mode_' + data.mode) }}</template>
      </Column>
      <Column field="format" :header="t('mxbackup_format')" style="width: 9rem" />
      <Column :header="t('mxbackup_encryption_short')" style="width: 9rem">
        <template #body="{ data }">
          <BoolMark :value="data.encryption_enabled" />
        </template>
      </Column>
      <Column field="description" :header="t('mxbackup_description')" />
      <Column :header="t('mxbackup_standard_masking_short')" style="width: 11rem">
        <template #body="{ data }">
          <BoolMark :value="data.standard_masking" />
        </template>
      </Column>
      <Column :header="t('mxbackup_active')" style="width: 8rem">
        <template #body="{ data }">
          <BoolMark :value="data.active" />
        </template>
      </Column>
      <Column field="editedon" :header="t('mxbackup_edited')" style="width: 12rem">
        <template #body="{ data }">{{ formatDate(data.editedon) }}</template>
      </Column>
      <Column v-if="can.manage" style="width: 6rem">
        <template #body="{ data }">
          <div class="mxb-rowactions">
            <Button
              icon="pi pi-pencil"
              severity="secondary"
              text
              rounded
              size="small"
              v-tooltip.top="t('mxbackup_edit')"
              :aria-label="t('mxbackup_edit')"
              @click="edit(data)"
            />
            <Button
              icon="pi pi-trash"
              severity="danger"
              text
              rounded
              size="small"
              :disabled="builtin(data)"
              v-tooltip.top="builtin(data) ? t('mxbackup_builtin_profile_remove') : t('mxbackup_remove')"
              :aria-label="t('mxbackup_remove')"
              @click="remove(data)"
            />
          </div>
        </template>
      </Column>
    </DataTable>

    <ProfileDialog
      v-model:visible="dialog.open"
      :profile="dialog.profile"
      @saved="saved"
    />
  </div>
</template>
