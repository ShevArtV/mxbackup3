<?php
/**
 * In-process smoke пакета mxBackup на стенде MODX 3.
 * Кладётся в корень www стенда, запускается php-8.x:
 *   php-8.3 _smoke_remote.php
 * Проверяет сервис, фасад, модель и все процессоры админки по FQCN.
 * Скрипт временный — после проверки удалить с сервера.
 */

use MODX\Revolution\modX;

define('MODX_API_MODE', true);

require_once __DIR__ . '/config.core.php';
require_once MODX_CORE_PATH . 'vendor/autoload.php';

$modx = modX::getInstance('mxbsmoke');
$modx->initialize('mgr');
$modx->getService('lexicon', 'modLexicon');
$modx->lexicon->load('mxbackup:default');

// Процессоры проверяют права, а в API-режиме пользователя нет: подставляем sudo.
$user = $modx->getObject(MODX\Revolution\modUser::class, ['username' => 'claude'])
    ?: $modx->getObject(MODX\Revolution\modUser::class, ['sudo' => 1]);
if ($user) {
    $modx->user = $user;
}

$results = [];
$check = function ($name, callable $fn) use (&$results) {
    try {
        $value = $fn();
        $results[] = [$name, 'OK', is_string($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE)];
    } catch (\Throwable $e) {
        $results[] = [$name, 'FAIL', get_class($e) . ': ' . $e->getMessage()];
    }
};

$check('services->get(mxbackup)', function () use ($modx) {
    $service = $modx->services->get('mxbackup');
    if (!$service instanceof MxBackup\Service) {
        throw new RuntimeException('сервис не зарегистрирован bootstrap-ом');
    }
    return get_class($service);
});

$check('фасад $modx->mxbackup', function () use ($modx) {
    if (!isset($modx->mxbackup)) {
        throw new RuntimeException('фасад не установлен');
    }
    return get_class($modx->mxbackup);
});

$check('модель MxBackupRun', function () use ($modx) {
    $count = $modx->getCount(MxBackup\Model\MxBackupRun::class);
    return "записей истории: {$count}";
});

$check('config()', function () use ($modx) {
    $config = $modx->services->get('mxbackup')->config(['profile' => 'prod']);
    return 'профиль ' . $config['profile']['name'] . ', формат ' . $config['profile']['format'];
});

$processors = [
    'Profile\\GetList' => [],
    'Run\\GetList' => ['limit' => 5],
    'Config\\Get' => [],
    'Config\\Validate' => ['profile' => 'prod'],
    'Database\\Table\\GetList' => ['profile_id' => 'prod', 'limit' => 5],
    'Database\\Column\\GetList' => ['profile_id' => 'dev', 'table' => 'modx_users'],
    'Rule\\GetList' => ['profile_id' => 'dev'],
];

foreach ($processors as $processor => $properties) {
    $check('процессор ' . $processor, function () use ($modx, $processor, $properties) {
        $class = 'MxBackup\\Processors\\Mgr\\' . $processor;
        if (!class_exists($class)) {
            throw new RuntimeException('класс не резолвится автолоадером');
        }
        $response = $modx->runProcessor($class, $properties);
        if (!$response) {
            throw new RuntimeException('пустой ответ процессора');
        }
        // runProcessor отдаёт либо JSON-строку, либо уже разобранный массив —
        // зависит от того, как процессор сформировал ответ.
        $raw = $response->getResponse();
        $body = is_array($raw) ? $raw : json_decode((string) $raw, true);
        if (!is_array($body)) {
            throw new RuntimeException('ответ не JSON: ' . substr((string) $raw, 0, 120));
        }
        if (array_key_exists('success', $body) && !$body['success']) {
            throw new RuntimeException('success=false: ' . ($body['message'] ?: 'без сообщения'));
        }
        if (isset($body['total'])) {
            return 'total=' . $body['total'];
        }
        return isset($body['object']) ? 'object: ' . substr(json_encode($body['object'], JSON_UNESCAPED_UNICODE), 0, 90) : 'ok';
    });
}

$check('лексикон mxbackup', function () use ($modx) {
    $entries = $modx->lexicon->fetch('mxbackup');
    if (count($entries) < 50) {
        throw new RuntimeException('в топике всего ' . count($entries) . ' ключей');
    }
    return count($entries) . ' ключей, mxbackup_save=' . ($entries['mxbackup_save'] ?? '—');
});

$check('бандл админки', function () {
    $path = MODX_ASSETS_PATH . 'components/mxbackup/js/mgr/vue-dist/mxbackup.min.js';
    if (!is_file($path)) {
        throw new RuntimeException('нет файла ' . $path);
    }
    $source = file_get_contents($path);
    preg_match_all('/from"([^"]+)"/', $source, $m);
    return 'внешние импорты: ' . implode(', ', array_unique($m[1]));
});

$failed = 0;
foreach ($results as [$name, $status, $detail]) {
    if ($status !== 'OK') {
        $failed++;
    }
    printf("%-42s %-5s %s\n", $name, $status, $detail);
}

echo $failed ? "\nПРОВАЛЕНО: {$failed}\n" : "\nВСЁ ЗЕЛЁНОЕ\n";
exit($failed ? 1 : 0);
