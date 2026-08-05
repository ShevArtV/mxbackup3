<?php

namespace MxBackup\Processors\Mgr\Restore;

use MxBackup\Model\MxBackupRun;
use MxBackup\Processors\Mgr\Base;

/**
 * Восстановление из архива истории. Перед изменением файлов или БД сам runner
 * снимает незамаскированную production-копию — здесь только вход и отчёт.
 */
class Create extends Base
{
    protected $permission = 'mxbackup_restore';

    public function process()
    {
        set_time_limit(0);

        $run = $this->modx->getObject(MxBackupRun::class, (int) $this->getProperty('id'));
        if (!$run) {
            return $this->failure($this->modx->lexicon('mxbackup_run_not_found'));
        }
        $path = (string) $run->get('archive_path');
        if ($path === '' || !is_file($path)) {
            return $this->failure($this->modx->lexicon('mxbackup_archive_missing'));
        }

        try {
            $service = $this->service();
            $config = $service->config(['profile' => 'prod']);
            $result = $service->restoreRunner()->restore(
                $config,
                $path,
                (string) $this->getProperty('scope', 'all'),
                (string) $this->getProperty('confirmation', ''),
                (string) $this->getProperty('password', ''),
                (string) $run->get('archive_checksum')
            );

            return $this->success($this->modx->lexicon('mxbackup_restore_done'), ['report' => $result->getReport()]);
        } catch (\Throwable $e) {
            return $this->failure($e->getMessage());
        }
    }
}
