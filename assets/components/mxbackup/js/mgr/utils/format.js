import { t } from './i18n.js';

export function formatBytes(value) {
  const bytes = parseInt(value || 0, 10);
  if (!bytes) return '—';
  if (bytes < 1024) return `${bytes} B`;
  if (bytes < 1048576) return `${(bytes / 1024).toFixed(1)} KB`;
  if (bytes < 1073741824) return `${(bytes / 1048576).toFixed(1)} MB`;
  return `${(bytes / 1073741824).toFixed(1)} GB`;
}

export function formatDate(unix) {
  if (!unix) return '—';
  const d = new Date(parseInt(unix, 10) * 1000);
  const p = (n) => String(n).padStart(2, '0');
  return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())} ${p(d.getHours())}:${p(d.getMinutes())}:${p(d.getSeconds())}`;
}

export function formatDuration(seconds) {
  if (seconds === null || seconds === undefined) return '—';
  const value = parseInt(seconds, 10);
  if (value < 60) return `${value} ${t('mxbackup_seconds_short')}`;
  const minutes = Math.floor(value / 60);
  const rest = value % 60;
  return `${minutes}:${String(rest).padStart(2, '0')}`;
}

/** Статус запуска → severity PrimeVue Tag. */
export function statusSeverity(status) {
  return { success: 'success', warning: 'warn', error: 'danger', running: 'info', cancelling: 'warn', cancelled: 'secondary' }[status] || 'secondary';
}

/** Действие обезличивания → severity: очистка данных заметнее замены. */
export function actionSeverity(action) {
  if (!action) return 'secondary';
  return action === 'truncate' || action === 'hide' ? 'warn' : 'info';
}
