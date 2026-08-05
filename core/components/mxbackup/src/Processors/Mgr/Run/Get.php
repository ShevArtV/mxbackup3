<?php

namespace MxBackup\Processors\Mgr\Run;

use MODX\Revolution\Processors\Model\GetProcessor;
use MxBackup\Model\MxBackupRun;

class Get extends GetProcessor
{
    public $classKey = MxBackupRun::class;
    public $languageTopics = ['mxbackup:default'];
    public $objectType = 'mxbackup_run';

    public function checkPermissions()
    {
        return $this->modx->hasPermission('mxbackup_view');
    }
}
