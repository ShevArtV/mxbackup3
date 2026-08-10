import { useApi } from '@vuetools/useApi';

// Конфиг страницы кладёт в <head> контроллер CMP.
const cfg = () => window.MxBackupConfig || {};

// Единый HTTP-слой на useApi (VueTools), а не свой fetch. Коннектор и токен —
// свои, поэтому передаются явно. Инициализация ленивая: конфиг попадает на
// страницу раньше модуля, но так безопаснее к порядку загрузки.
let _api = null;
function api() {
  if (!_api) {
    const c = cfg();
    _api = useApi({ baseUrl: c.connector_url, authToken: c.token });
  }
  return _api;
}

// useApi БРОСАЕТ при !res.ok и при success === false (тело в err.data), поэтому
// весь фронт строится на try/catch. X-Requested-With useApi сам не шлёт.
// Свой headers перекрывает дефолтный целиком (useApi спредит opts последним),
// поэтому Accept задаём здесь же.
const OPTS = { headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' } };
const post = (action, params = {}) => api().post(action, params, OPTS);

const P = 'MxBackup\\Processors\\Mgr\\';

/** Текст ошибки проваленного вызова: тело ответа MODX лежит в err.data. */
export function errorMessage(err, fallback = '') {
  if (!err) return fallback;
  const body = err.data ?? err;
  if (body.message) return String(body.message);
  if (Array.isArray(body.errors) && body.errors.length) {
    const parts = body.errors.map((e) => e.msg || e.message).filter(Boolean);
    if (parts.length) return parts.join('; ');
  }
  return err.message ? String(err.message) : fallback;
}

/** Список из ответа: results (списочные процессоры) или object. */
export function listOf(res) {
  if (!res) return [];
  if (Array.isArray(res.results)) return res.results;
  if (Array.isArray(res.object)) return res.object;
  return [];
}

export const ProfileApi = {
  getList: (params = {}) => post(P + 'Profile\\GetList', params),
  create: (data) => post(P + 'Profile\\Create', data),
  update: (data) => post(P + 'Profile\\Update', data),
  remove: (id) => post(P + 'Profile\\Remove', { id }),
};

export const TableApi = {
  getList: (params = {}) => post(P + 'Database\\Table\\GetList', params),
  // operation: toggle | set_all | mode | replace
  update: (params = {}) => post(P + 'Profile\\Tables\\Update', params),
};

export const ColumnApi = {
  getList: (params = {}) => post(P + 'Database\\Column\\GetList', params),
};

export const RuleApi = {
  // Действие уходит как rule_action: параметр action в коннекторе занят
  // именем процессора.
  create: (params = {}) => post(P + 'Rule\\Create', params),
  update: (params = {}) => post(P + 'Rule\\Update', params),
  remove: (profileId, id) => post(P + 'Rule\\Remove', { profile_id: profileId, id }),
};

export const RunApi = {
  getList: (params = {}) => post(P + 'Run\\GetList', params),
  estimate: (profile) => post(P + 'Run\\Estimate', { profile }),
  create: (profile, dryRun = false) =>
    post(P + 'Run\\Create', { profile, dry_run: dryRun ? 1 : 0 }),
  cancel: (id = 0) => post(P + 'Run\\Cancel', { id }),
};

export const ConfigApi = {
  get: () => post(P + 'Config\\Get'),
  update: (values) => post(P + 'Config\\Update', values),
  validate: (profile) => post(P + 'Config\\Validate', { profile }),
};

export const RestoreApi = {
  preflight: (id, password) => post(P + 'Restore\\Preflight', { id, password }),
  restore: (params = {}) => post(P + 'Restore\\Create', params),
};

export const config = cfg;
