import { createApp } from 'vue';
// Vue/PrimeVue берутся из Import Map пакета VueTools и не бандлятся.
// Тема (Aura) и PrimeIcons тоже приходят оттуда — vuetools.css.
import { PrimeVue, Aura, ConfirmationService, ToastService, Tooltip } from 'primevue';
import App from './App.vue';
import './styles/app.css';

const app = createApp(App);
// darkModeSelector отключён: своей тёмной темы у менеджера MODX 3 нет, а Aura
// по умолчанию следует prefers-color-scheme и чернит поля внутри светлой админки.
app.use(PrimeVue, { theme: { preset: Aura, options: { darkModeSelector: false } } });
app.use(ConfirmationService);
app.use(ToastService);
app.directive('tooltip', Tooltip);
app.mount('#mxbackup-app');
