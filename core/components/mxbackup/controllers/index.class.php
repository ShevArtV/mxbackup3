<?php

use MODX\Revolution\modExtraManagerController;

/**
 * CMP mxBackup — единая страница на Vue 3.
 *
 * Имя класса плоское и в глобальном namespace: так его строит и ищет
 * MODX\Revolution\modManagerResponse::getControllerClassName() для пары
 * namespace=mxbackup + action=index (ucfirst(namespace) . action .
 * 'ManagerController'), подгружая файл controllers/index.class.php. PSR-4
 * src/Controllers здесь не резолвится.
 */
class MxbackupIndexManagerController extends modExtraManagerController
{
    public function getLanguageTopics()
    {
        return ['mxbackup:default'];
    }

    public function checkPermissions()
    {
        return $this->modx->hasPermission('mxbackup_view');
    }

    public function getPageTitle()
    {
        return $this->modx->lexicon('mxbackup');
    }

    public function getTemplateFile()
    {
        return MODX_CORE_PATH . 'components/mxbackup/templates/mgr/index.tpl';
    }

    public function loadCustomCssJs()
    {
        $assetsUrl = MODX_ASSETS_URL . 'components/mxbackup/';

        $config = [
            'connector_url' => $assetsUrl . 'connector.php',
            'token' => $this->modx->user
                ? $this->modx->user->getUserToken($this->modx->context->get('key'))
                : '',
            'assets_url' => $assetsUrl,
            'default_profile' => $this->modx->getOption('mxbackup.default_profile', null, 'prod'),
            'permissions' => [
                'manage' => $this->modx->hasPermission('mxbackup_manage'),
                'run' => $this->modx->hasPermission('mxbackup_run'),
                'restore' => $this->modx->hasPermission('mxbackup_restore'),
            ],
        ];
        $json = json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $this->modx->regClientStartupHTMLBlock("<script>window.MxBackupConfig = {$json};</script>");

        $this->registerLexicon();
        $this->registerVueToolsCheck();

        $distPath = MODX_ASSETS_PATH . 'components/mxbackup/js/mgr/vue-dist/';
        $distUrl = $assetsUrl . 'js/mgr/vue-dist/';
        // Cache-bust по mtime бандла — каждая пересборка сбрасывает кэш сама.
        $ver = @filemtime($distPath . 'mxbackup.min.js') ?: '2.0.0';

        if (is_file($distPath . 'mxbackup.min.css')) {
            $cssVer = @filemtime($distPath . 'mxbackup.min.css') ?: $ver;
            $this->modx->regClientCSS($distUrl . 'mxbackup.min.css?v=' . rawurlencode((string) $cssVer));
        }

        // Entry — ES-модуль; Vue/PrimeVue резолвятся из Import Map VueTools.
        // addJavascript() здесь не годится: он не ставит type="module".
        $this->modx->regClientStartupHTMLBlock(
            '<script type="module" data-vue-module src="'
            . $distUrl . 'mxbackup.min.js?v=' . rawurlencode((string) $ver) . '"></script>'
        );
    }

    /**
     * useLexicon читает только то, что уже лежит в MODx.lang — load() в
     * VueTools не реализован. Поэтому записи топика прокидываются сюда сами.
     */
    protected function registerLexicon(): void
    {
        $this->modx->lexicon->load('mxbackup:default');
        $entries = $this->modx->lexicon->fetch('mxbackup');
        $json = json_encode($entries, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $this->modx->regClientStartupHTMLBlock(
            "<script>MODx.lang = Object.assign(MODx.lang || {}, {$json});</script>"
        );
    }

    /**
     * Inline-проверка Import Map пакета VueTools: без него модуль не
     * резолвится и в консоли остаётся «Failed to resolve module specifier».
     * Объявить transport-зависимость от VueTools сборщик не умеет, поэтому
     * страховка рантаймовая.
     */
    protected function registerVueToolsCheck(): void
    {
        $title = json_encode($this->modx->lexicon('mxbackup_error') ?: 'mxBackup', JSON_UNESCAPED_UNICODE);
        $message = json_encode(
            $this->modx->lexicon('mxbackup_vuetools_required')
                ?: 'Для работы интерфейса нужен пакет VueTools.',
            JSON_UNESCAPED_UNICODE
        );

        $script = <<<JS
<script>
(function () {
    var map = document.querySelector('script[type="importmap"]');
    var ok = false;
    if (map) {
        try { var j = JSON.parse(map.textContent); ok = !!(j.imports && j.imports.vue); } catch (e) { ok = false; }
    }
    if (!ok) {
        document.querySelectorAll('script[type="module"][data-vue-module]').forEach(function (el) { el.remove(); });
        var alertFn = function () {
            if (typeof MODx !== 'undefined' && MODx.msg) { MODx.msg.alert({$title}, {$message}); }
            else { alert({$message}); }
        };
        if (typeof Ext !== 'undefined') { Ext.onReady(alertFn); }
        else { document.addEventListener('DOMContentLoaded', function () { setTimeout(alertFn, 500); }); }
    }
})();
</script>
JS;
        $this->modx->regClientStartupHTMLBlock($script);
    }
}
