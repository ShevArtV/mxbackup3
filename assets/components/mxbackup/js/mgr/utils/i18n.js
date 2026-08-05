import { useLexicon } from '@vuetools/useLexicon';

// Единая точка локализации на VueTools useLexicon. Строки НЕ хардкодятся в
// компонентах — только ключи; тексты живут в lexicon/{ru,en}/default.inc.php и
// прокидываются в window.MODx.lang контроллером CMP (useLexicon.load() не
// реализован и читает только то, что уже лежит в MODx.lang).
const { _, has, getByPrefix } = useLexicon();

export const t = _;
export { has, getByPrefix };
