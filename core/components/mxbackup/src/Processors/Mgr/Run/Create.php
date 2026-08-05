<?php

namespace MxBackup\Processors\Mgr\Run;

use MxBackup\Processors\Mgr\Base;

class Create extends Base
{
    public $permission = 'mxbackup_run';

    public function process()
    {
        set_time_limit(0);

        try {
            $service = $this->service();
            $config = $service->config(['profile' => (string) $this->getProperty('profile', 'prod')]);
            $result = $service->runner()->run($config, (bool) $this->getProperty('dry_run', false));

            $message = $result->getArchivePath()
                ? $this->modx->lexicon('mxbackup_run_archive', ['path' => $result->getArchivePath()])
                : $this->modx->lexicon('mxbackup_run_dryrun_done');

            return $this->success($message, [
                'archive' => $result->getArchivePath(),
                'report' => $result->getReport(),
            ]);
        } catch (\Throwable $e) {
            return $this->failure($e->getMessage());
        }
    }
}
