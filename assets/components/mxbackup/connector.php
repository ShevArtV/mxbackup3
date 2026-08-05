<?php

/**
 * Коннектор mxBackup — точка входа AJAX-запросов админки.
 * Параметр `action` — FQCN процессора (например
 * MxBackup\Processors\Mgr\Run\GetList); ядро резолвит его через autoload,
 * который регистрирует bootstrap пакета вместе с моделью.
 */

require_once dirname(__DIR__, 3) . '/config.core.php';
require_once MODX_CORE_PATH . 'config/' . MODX_CONFIG_KEY . '.inc.php';
require_once MODX_CONNECTORS_PATH . 'index.php';

/** @var \MODX\Revolution\modX $modx */
$modx->lexicon->load('mxbackup:default');

$modx->request->handleRequest();
