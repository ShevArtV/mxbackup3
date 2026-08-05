<?php

namespace MxBackup;

use MODX\Revolution\modX;
use MxBackup\Core\BackupRunner;
use MxBackup\Core\Config\ConfigLoader;
use MxBackup\Core\Config\ConfigResolver;
use MxBackup\Core\Config\Defaults;
use MxBackup\Core\RestoreRunner;
use MxBackup\Platform\Modx3\Modx3Platform;

/**
 * Точка входа пакета под MODX 3: собирает платформу, конфигурацию и runner'ы.
 *
 * В линии MODX 2 те же операции делает статический MxBackup\Bootstrap; здесь
 * это сервис контейнера ($modx->services->get('mxbackup')), потому что тройка
 * даёт DI-контейнер, а getService() для пакетов больше не используется.
 */
final class Service
{
    /** @var modX */
    private $modx;

    /** @var Modx3Platform|null */
    private $platform;

    public function __construct(modX $modx)
    {
        $this->modx = $modx;
    }

    public function platform()
    {
        return $this->platform ?: $this->platform = new Modx3Platform($this->modx);
    }

    /**
     * Порядок разрешения конфигурации (снизу вверх по приоритету):
     * встроенные значения → системные настройки MODX → файловый профиль →
     * общий PHP-файл дополнительных настроек → аргументы CLI.
     */
    public function config(array $cli = [])
    {
        $platform = $this->platform();
        $system = [
            'storage_path' => $platform->getOption('mxbackup.storage_path', ''),
            'config_path' => $platform->getOption('mxbackup.config_path', ''),
            'default_profile' => $platform->getOption('mxbackup.default_profile', 'prod'),
            'format' => $platform->getOption('mxbackup.archive_format', 'tar.gz'),
            'mail' => [
                'enabled' => (bool)$platform->getOption('mxbackup.mail_enabled', false),
                'to' => $platform->getOption('mxbackup.mail_to', ''),
                'max_attachment_mb' => (float)$platform->getOption('mxbackup.mail_max_attachment_mb', 10),
            ],
            'retention' => [
                'days' => (int)$platform->getOption('mxbackup.retention_days', 30),
                'count' => (int)$platform->getOption('mxbackup.retention_count', 10),
            ],
            'lock_ttl_minutes' => (int)$platform->getOption('mxbackup.lock_ttl_minutes', 720),
        ];

        $configPath = isset($cli['config_path']) && $cli['config_path'] !== ''
            ? $cli['config_path']
            : ($system['config_path'] ?: $platform->getCorePath() . 'config/mxbackup.php');
        $file = is_file($configPath) ? (new ConfigLoader())->load($configPath) : [];
        unset($cli['config_path']);

        return (new ConfigResolver())->resolve(
            Defaults::values(),
            $system,
            $platform->profiles()->all(),
            $file,
            $cli
        );
    }

    public function runner()
    {
        return new BackupRunner($this->platform());
    }

    public function restoreRunner()
    {
        return new RestoreRunner($this->platform());
    }

    /**
     * Достаёт сервис из контейнера; при вызове до bootstrap (CLI, резолверы
     * установки) поднимает его сам.
     */
    public static function fromModx(modX $modx)
    {
        try {
            if ($modx->services->has('mxbackup')) {
                $service = $modx->services->get('mxbackup');
                if ($service instanceof self) {
                    return $service;
                }
            }
        } catch (\Throwable $e) {
            // Контейнер может быть не готов — поднимаем сервис напрямую.
        }

        $service = new self($modx);
        $modx->services->add('mxbackup', $service);
        return $service;
    }
}
