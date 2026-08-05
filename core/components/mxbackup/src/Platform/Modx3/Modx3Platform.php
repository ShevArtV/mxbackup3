<?php

namespace MxBackup\Platform\Modx3;

use MODX\Revolution\modX;
use MxBackup\Core\Contract\PlatformInterface;

final class Modx3Platform implements PlatformInterface
{
    private $modx;
    private $database;
    private $profiles;
    private $runs;
    private $mailer;

    public function __construct(modX $modx)
    {
        $this->modx = $modx;
        // Модель может понадобиться и вне HTTP-запроса (CLI, cron), где
        // bootstrap namespace ядром не выполнялся.
        if (!isset($modx->packages['MxBackup\\Model'])) {
            $corePath = $modx->getOption('mxbackup.core_path', null, MODX_CORE_PATH . 'components/mxbackup/');
            $modx->addPackage('MxBackup\\Model', $corePath . 'src/', null, 'MxBackup\\');
        }
    }

    public function getOption($key, $default = null) { return $this->modx->getOption($key, null, $default); }
    public function getSiteRoot() { return $this->modx->getOption('base_path', null, MODX_BASE_PATH); }
    public function getCorePath() { return $this->modx->getOption('core_path', null, MODX_CORE_PATH); }
    public function getPlatformVersion() { return $this->modx->getVersionData()['full_version']; }
    public function now() { return time(); }

    public function log($level, $message, array $context = [])
    {
        $logger = $this->resolveLogger();
        if ($logger && method_exists($logger, 'log')) {
            $options = ['skip_classes' => [__CLASS__]];
            if (!empty($context['run_id'])) {
                $options['process_uid'] = 'mxbackup_' . (int) $context['run_id'];
            }
            try {
                $logger->log(['mxbackup', 'backup'], $level, (string) $message, $context, $options);
                return;
            } catch (\Throwable $e) {
                // mxLogger is optional. A broken service must not hide the original event.
            }
        }

        $levels = [
            'debug' => modX::LOG_LEVEL_DEBUG,
            'info' => modX::LOG_LEVEL_INFO,
            'warning' => modX::LOG_LEVEL_WARN,
            'error' => modX::LOG_LEVEL_ERROR,
        ];
        $suffix = $context ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
        $this->modx->log(isset($levels[$level]) ? $levels[$level] : modX::LOG_LEVEL_ERROR, '[mxbackup] ' . $message . $suffix);
    }

    /**
     * mxLogger под MODX 3 живёт в контейнере сервисов, а короткий фасад
     * $modx->mxl регистрирует его же bootstrap. Оба пути опциональны:
     * без установленного mxLogger пишем в стандартный журнал MODX.
     */
    private function resolveLogger()
    {
        if (isset($this->modx->mxl) && is_object($this->modx->mxl)) {
            return $this->modx->mxl;
        }
        try {
            if ($this->modx->services->has('mxlogger')) {
                $logger = $this->modx->services->get('mxlogger');
                return is_object($logger) ? $logger : null;
            }
        } catch (\Throwable $e) {
            return null;
        }
        return null;
    }

    public function database() { return $this->database ?: $this->database = new DatabaseAdapter($this->modx); }
    public function profiles() { return $this->profiles ?: $this->profiles = new ProfileRepository($this->modx); }
    public function runs() { return $this->runs ?: $this->runs = new RunRepository($this->modx); }
    public function mailer() { return $this->mailer ?: $this->mailer = new Mailer($this->modx); }
    public function getModx() { return $this->modx; }
}
