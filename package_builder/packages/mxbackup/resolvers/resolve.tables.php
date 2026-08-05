<?php

/**
 * Resolver: создание таблицы mxbackup_run при install/upgrade.
 *
 * Таблица НЕ дропается при uninstall — история запусков переживает
 * переустановку пакета. Charset приводится к utf8mb4 с порога:
 * createObjectContainer создаёт таблицу в дефолтном charset сервера, и на
 * latin1-сервере вставка кириллицы в отчёт падает с ошибкой 1366.
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

if ($action === xPDOTransport::ACTION_UNINSTALL) {
    return true;
}

$corePath = $modx->getOption('core_path') . 'components/mxbackup/';

if (!isset($modx->packages['MxBackup\\Model'])) {
    $modx->addPackage('MxBackup\\Model', $corePath . 'src/', null, 'MxBackup\\');
}

$manager = $modx->getManager();
$manager->createObjectContainer(\MxBackup\Model\MxBackupRun::class);

try {
    $table = $modx->getTableName(\MxBackup\Model\MxBackupRun::class);
    if ($table) {
        $bare = trim($table, '`');
        $statement = $modx->query('SHOW TABLE STATUS LIKE ' . $modx->quote($bare));
        $row = $statement ? $statement->fetch(\PDO::FETCH_ASSOC) : null;
        $collation = $row['Collation'] ?? '';
        if (stripos((string) $collation, 'utf8mb4') === false) {
            $modx->exec("ALTER TABLE {$table} CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $modx->log(modX::LOG_LEVEL_INFO, '[mxbackup] Таблица mxbackup_run приведена к utf8mb4.');
        }
    }
} catch (\Throwable $e) {
    $modx->log(modX::LOG_LEVEL_WARN, '[mxbackup] Не удалось enforce utf8mb4: ' . $e->getMessage());
}

$modx->log(modX::LOG_LEVEL_INFO, '[mxbackup] Таблица mxbackup_run проверена/создана.');

return true;
