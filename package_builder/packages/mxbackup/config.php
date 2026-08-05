<?php

/**
 * Конфигурация сборки transport-пакета mxBackup для MODX 3.
 *
 * Сборщик (shevartv/modx-builder, CLI modxapp) ставится глобально, но его
 * конфиг обязан лежать в репозитории — иначе на другой машине пакет не собрать.
 *
 * Линия MODX 2 живёт в отдельном репозитории (apps/mxbackup) и нумеруется 1.x;
 * здесь мажор означает платформу — 2.x = MODX 3.
 */

return [
    'name' => 'mxBackup',
    'name_lower' => 'mxbackup',
    'name_short' => 'mxb',
    'version' => '2.0.0',
    'release' => 'beta',
    'php_version' => '8.1',

    'paths' => [
        'core' => 'core/components/mxbackup/',
        'assets' => 'assets/components/mxbackup/',
    ],

    // ⚠️ Регистр важен: генератор моделей срезает этот префикс из package
    // схемы, чтобы получить путь. Без него (при name = mxBackup) классы уедут
    // в src/MxBackup/Model/ вместо src/Model/, и PSR-4 их не найдёт.
    'namespace_prefix' => 'MxBackup',

    'schema' => [
        'file' => 'schema/mxbackup.mysql.schema.xml',
        // Классы модели генерируются `modxapp schema mxbackup` и коммитятся:
        // в сборке они уже готовы, а таблицу создаёт резолвер.
        'auto_generate_classes' => true,
        'update_tables' => false,
    ],

    'elements' => [
        'category' => 'mxBackup',
        'settings' => 'elements/settings.php',
        'menus' => 'elements/menus.php',
    ],

    'static' => [
        'chunks' => false,
        'snippets' => false,
        'plugins' => false,
    ],

    'encrypt' => false,

    'tools' => [
        'analyse' => '',
        'cs' => '',
        'csMode' => 'fix',
    ],

    'build' => [
        'download' => false,
        'install' => false,
        'update' => [
            'plugins' => true,
            // Настройки на сайте могли быть изменены руками — не перетираем.
            'settings' => false,
            'events' => true,
            'menus' => true,
        ],
    ],
];
