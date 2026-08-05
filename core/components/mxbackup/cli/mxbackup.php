#!/usr/bin/env php
<?php

/**
 * CLI mxBackup для MODX 3.
 *
 * Ядро тройки поднимается через composer-автолоадер (core/vendor/autoload.php),
 * а классы самого пакета подхватывает bootstrap namespace: addPackage
 * регистрирует PSR-4 «MxBackup\ → src/» в том же автолоадере.
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "mxBackup CLI доступен только из командной строки.\n");
    exit(2);
}

$root = dirname(__DIR__, 4);
foreach ($argv as $argument) {
    if (strpos($argument, '--modx-root=') === 0) {
        $root = rtrim(substr($argument, strlen('--modx-root=')), '/\\');
        break;
    }
}
if (!is_file($root . '/config.core.php')) {
    fwrite(STDERR, "Не найден config.core.php. Передайте --modx-root=/path/to/site\n");
    exit(2);
}

define('MODX_API_MODE', true);
require_once $root . '/config.core.php';
require_once MODX_CORE_PATH . 'vendor/autoload.php';

$modx = new MODX\Revolution\modX();
$modx->initialize('mgr');
$modx->getService('error', MODX\Revolution\Error\modError::class);

// В API-режиме extension packages не грузятся — регистрируем модель и сервис
// сами, тем же bootstrap, что выполняет ядро в веб-запросе.
$corePath = $modx->getOption('mxbackup.core_path', null, MODX_CORE_PATH . 'components/mxbackup/');
$namespace = ['path' => $corePath];
require_once $corePath . 'bootstrap.php';

$application = new MxBackup\Cli\Application($modx);
exit($application->run($argv));
