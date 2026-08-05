import { ref } from 'vue';
import { ProfileApi, config, listOf } from '../api/connector.js';

// Список профилей нужен четырём экранам сразу (запуск, таблицы, обезличивание,
// настройки). Держим один разделяемый стейт, чтобы не гонять один и тот же
// запрос по разу на вкладку и не расходиться в данных после правки профиля.
const profiles = ref([]);
const loading = ref(false);
let loaded = false;

async function load(force = false) {
  if (loading.value || (loaded && !force)) return profiles.value;
  loading.value = true;
  try {
    const res = await ProfileApi.getList({ limit: 0 });
    profiles.value = listOf(res);
    loaded = true;
  } catch (e) {
    profiles.value = [];
  } finally {
    loading.value = false;
  }
  return profiles.value;
}

/** Имя профиля по умолчанию: из настроек, иначе первый доступный. */
function defaultProfile(preferred) {
  const wanted = preferred || config().default_profile || 'prod';
  const found = profiles.value.find((p) => p.name === wanted);
  return (found || profiles.value[0] || {}).name || '';
}

export function useProfiles() {
  return { profiles, loading, load, defaultProfile };
}
