<?php

/**
 * Resolver: файловые профили prod и dev.
 *
 * Профили живут по одному PHP-файлу в каталоге из настройки
 * mxbackup.config_dir; существующие не перезаписываются — на upgrade
 * пользовательская правка профиля должна пережить установку.
 *
 * Миграции из таблиц mxbackup_profile/mxbackup_rule здесь нет намеренно:
 * они существовали только в линии MODX 2 (до 1.1.0), а эта линия начинается
 * сразу с файловых профилей.
 *
 * @var \xPDO\Transport\xPDOTransport $transport
 * @var array $options
 */

use MODX\Revolution\modX;
use xPDO\Transport\xPDOTransport;

if (!$transport->xpdo) {
    return true;
}

/** @var modX $modx */
$modx = $transport->xpdo;
$action = $options[xPDOTransport::PACKAGE_ACTION] ?? '';

if (!in_array($action, [xPDOTransport::ACTION_INSTALL, xPDOTransport::ACTION_UPGRADE], true)) {
    return true;
}

$corePath = $modx->getOption('core_path') . 'components/mxbackup/';

if (!isset($modx->packages['MxBackup\\Model'])) {
    $modx->addPackage('MxBackup\\Model', $corePath . 'src/', null, 'MxBackup\\');
}

$store = (new \MxBackup\Platform\Modx3\ProfileRepository($modx))->getStore();

$defaults = [
    'prod' => [
        'name' => 'prod',
        'mode' => 'prod',
        'active' => true,
        'description' => 'Полный аварийный backup',
        'format' => 'tar.gz',
        'encryption' => ['enabled' => false, 'password' => ''],
        'files' => [
            'include' => ['*'],
            'exclude' => ['core/cache/', 'core/packages/', 'assets/cache/'],
        ],
        'database' => ['include_tables' => ['*'], 'exclude_tables' => []],
        'masking' => ['standard' => false, 'rules' => []],
    ],
    'dev' => [
        'name' => 'dev',
        'mode' => 'dev',
        'active' => true,
        'description' => 'Обезличенный backup для разработки',
        'format' => 'tar.gz',
        'encryption' => ['enabled' => false, 'password' => ''],
        'files' => [
            'include' => ['*'],
            'exclude' => [
                'core/cache/', 'core/packages/', 'core/config/',
                'assets/cache/', 'assets/uploads/private/',
            ],
        ],
        'database' => ['include_tables' => ['*'], 'exclude_tables' => []],
        'masking' => ['standard' => true, 'rules' => []],
    ],
];

foreach ($defaults as $name => $profile) {
    if ($store->find($name)) {
        continue;
    }
    $profile['createdon'] = time();
    $profile['editedon'] = time();
    try {
        $store->save($profile);
        $modx->log(modX::LOG_LEVEL_INFO, '[mxbackup] Создан профиль ' . $name . '.');
    } catch (\Throwable $e) {
        $modx->log(modX::LOG_LEVEL_ERROR, '[mxbackup] Не удалось создать профиль ' . $name . ': ' . $e->getMessage());
    }
}

// Настройки уже в базе, но getOption() до сброса кэша отдаёт старые значения.
$modx->getCacheManager()->refresh(['system_settings' => []]);

return true;
