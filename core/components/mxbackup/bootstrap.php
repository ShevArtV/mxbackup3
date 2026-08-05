<?php

/**
 * mxBackup bootstrap — регистрация xPDO-пакета модели и DI-сервиса.
 * Загружается ядром MODX 3 при инициализации namespace «mxbackup».
 *
 * addPackage с namespace-префиксом попутно регистрирует PSR-4 «MxBackup\ →
 * src/» в composer-автолоадере ядра (xPDO::setPackageMeta), поэтому весь код
 * пакета резолвится дальше без собственного автолоадера.
 *
 * Доступ к сервису: $modx->services->get('mxbackup') или фасад $modx->mxbackup.
 *
 * @var \MODX\Revolution\modX $modx
 * @var array $namespace
 */

$modx->addPackage('MxBackup\Model', $namespace['path'] . 'src/', null, 'MxBackup\\');

$modx->services->add('mxbackup', function ($c) use ($modx) {
    return new MxBackup\Service($modx);
});

// Короткий фасад: $modx->mxbackup->runner()->run('prod') из сниппета или
// плагина. xPDO помечен #[AllowDynamicProperties], свойство безопасно в 8.2+.
// Конструктор дешёвый: платформа и конфигурация читаются лениво.
if (!isset($modx->mxbackup)) {
    $modx->mxbackup = $modx->services->get('mxbackup');
}
