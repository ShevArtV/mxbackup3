import { createApp } from 'vue';
// Vue/PrimeVue берутся из Import Map пакета VueTools и не бандлятся.
// ⚠️ vuetools.css везёт только PrimeIcons — стилей компонентов там нет,
// поэтому тему приходится включать здесь (styled-режим), а её глобальные
// правила ограничивать вручную: см. scopeGlobalStyles().
import { PrimeVue, Aura, ConfirmationService, ToastService, Tooltip } from 'primevue';
import App from './App.vue';
import { scopeGlobalStyles } from './utils/scope-styles.js';
import './styles/app.css';

const app = createApp(App);
// darkModeSelector отключён: своей тёмной темы у менеджера MODX 3 нет, а Aura
// по умолчанию следует prefers-color-scheme и чернит поля внутри светлой админки.
app.use(PrimeVue, { theme: { preset: Aura, options: { darkModeSelector: false } } });
app.use(ConfirmationService);
app.use(ToastService);
app.directive('tooltip', Tooltip);
app.mount('#mxbackup-app');

// PrimeVue инжектит глобальный reset (box-sizing) на всю страницу и ломает
// вёрстку родной админки MODX — ограничиваем его нашим приложением.
scopeGlobalStyles();
