<script setup>
import { t } from '../utils/i18n.js';

// Булев признак в таблице: одна и та же отметка во всех колонках, иначе строка
// рябит тремя разными способами показать «да».
defineProps({ value: { type: Boolean, default: false } });
</script>

<template>
  <span class="mxb-bool" :class="{ 'mxb-bool--on': value }" :title="value ? t('mxbackup_yes') : t('mxbackup_no')">
    <i :class="value ? 'pi pi-check' : 'pi pi-minus'" aria-hidden="true"></i>
    <span class="mxb-visually-hidden">{{ value ? t('mxbackup_yes') : t('mxbackup_no') }}</span>
  </span>
</template>
