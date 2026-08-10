<?php

namespace MxBackup\Processors\Mgr\Run;

use MxBackup\Core\BackupEstimator;
use MxBackup\Processors\Mgr\Base;

class Estimate extends Base
{
    public $permission = 'mxbackup_run';

    public function process()
    {
        set_time_limit(0);
        try {
            $service = $this->service();
            $config = $service->config(['profile' => (string) $this->getProperty('profile', 'prod')]);
            return $this->success('', (new BackupEstimator($service->platform()))->estimate($config));
        } catch (\Throwable $e) {
            return $this->failure($e->getMessage());
        }
    }
}
