/**
 * Ограничивает глобальные правила PrimeVue пределами нашего приложения.
 *
 * PrimeVue в styled-режиме инжектит в <head> тег
 * `<style data-primevue-style-id="global-style">`, а в нём — reset
 * `*, ::before, ::after { box-sizing: border-box }` и правило `.pi`.
 * Оба применяются ко всей странице, то есть меняют вёрстку родной админки
 * MODX: ExtJS рассчитан на content-box. Отключить тему нельзя — стили
 * компонентов приходят именно оттуда (в vuetools.css лежат только PrimeIcons).
 *
 * Поэтому переписываем селекторы через CSSOM: правило остаётся, но действует
 * только на наш контейнер и на компоненты PrimeVue (диалоги и тосты
 * телепортируются, но всегда несут класс .p-component).
 */

const SCOPE = '.vueApp';
const COMPONENT = '.p-component';

/** Селекторы, которые нельзя оставлять глобальными. */
const GLOBAL = new Set(['*', '::before', '::after', '.pi']);

function scopeSelector(part) {
  if (part === '*') {
    return `${SCOPE}, ${SCOPE} *, ${COMPONENT}, ${COMPONENT} *`;
  }
  // ::before / ::after / .pi — цепляем к обоим корням.
  const joiner = part.startsWith('::') ? '' : ' ';
  return `${SCOPE} ${part}, ${SCOPE} *${joiner}${part}, ${COMPONENT}${joiner}${part}, ${COMPONENT} *${joiner}${part}`;
}

function scopeSheet(styleEl) {
  const sheet = styleEl.sheet;
  if (!sheet) return false;

  let changed = false;
  for (let i = sheet.cssRules.length - 1; i >= 0; i--) {
    const rule = sheet.cssRules[i];
    if (!rule.selectorText) continue;

    const parts = rule.selectorText.split(',').map((p) => p.trim());
    if (!parts.some((p) => GLOBAL.has(p))) continue;

    const scoped = parts.map((p) => (GLOBAL.has(p) ? scopeSelector(p) : p)).join(', ');
    const text = rule.cssText.replace(rule.selectorText, scoped);
    try {
      sheet.deleteRule(i);
      sheet.insertRule(text, i);
      changed = true;
    } catch (e) {
      // Правило не переписалось — оставляем как есть, ломать страницу нельзя.
    }
  }
  return changed;
}

export function scopeGlobalStyles() {
  const apply = () => {
    for (const el of document.querySelectorAll('style[data-primevue-style-id]')) {
      if (el.dataset.mxbScoped) continue;
      if (scopeSheet(el)) el.dataset.mxbScoped = '1';
    }
  };

  apply();
  // Стили части компонентов инжектятся при их первом рендере — догоняем их,
  // пока приложение раскрывает вкладки и диалоги.
  const observer = new MutationObserver(apply);
  observer.observe(document.head, { childList: true });
}
