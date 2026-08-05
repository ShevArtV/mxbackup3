<?php

namespace MxBackup\Processors\Mgr\Restore;

use MxBackup\Model\MxBackupRun;
use MxBackup\Processors\Mgr\Base;

/**
 * Проверка архива до восстановления: manifest, checksum, пути, мажор MODX.
 * Возвращает код подтверждения, без которого Create не выполняется.
 */
class Preflight extends Base
{
    public $permission = 'mxbackup_restore';

    public function process()
    {
        $run = $this->modx->getObject(MxBackupRun::class, (int) $this->getProperty('id'));
        if (!$run) {
            return $this->failure($this->modx->lexicon('mxbackup_run_not_found'));
        }
        $path = (string) $run->get('archive_path');
        if ($path === '' || !is_file($path)) {
            return $this->failure($this->modx->lexicon('mxbackup_archive_missing'));
        }

        try {
            $info = $this->service()->restoreRunner()->preflight(
                $path,
                (string) $this->getProperty('password', ''),
                (string) $run->get('archive_checksum')
            );

            return $this->success($this->modx->lexicon('mxbackup_preflight_passed'), $info);
        } catch (\Throwable $e) {
            return $this->failure($e->getMessage());
        }
    }
}
