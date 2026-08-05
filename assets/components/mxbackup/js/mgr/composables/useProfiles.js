import { ref } from 'vue';
import { ProfileApi, config, listOf } from '../api/connector.js';

// Список профилей нужен четырём экранам сразу (запуск, таблицы, обезличивание,
// настройки). Держим один разделяемый стейт, чтобы не гонять один и тот же
// запрос по разу на вкладку и не расходиться в данных после правки профиля.
const profiles = ref([]);
const loading = ref(false);
let loaded = false;
// Панели вкладок монтируются одновременно и зовут load() параллельно. Без
// общего промиса второй вызов возвращал ещё пустой список, и вкладка навсегда
// оставалась без выбранного профиля.
let inflight = null;

async function load(force = false) {
  if (loaded && !force) return profiles.value;
  if (inflight) return inflight;

  loading.value = true;
  // Ошибку не глотаем: молчаливый пустой список неотличим от «профилей нет»,
  // а на деле это может быть упавший процессор. Показывать её — дело вызывающего.
  inflight = (async () => {
    try {
      const res = await ProfileApi.getList({ limit: 0 });
      profiles.value = listOf(res);
      loaded = true;
      return profiles.value;
    } finally {
      loading.value = false;
      inflight = null;
    }
  })();

  return inflight;
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
