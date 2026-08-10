<?php

namespace MxBackup\Processors\Mgr\Run;

use MxBackup\Processors\Mgr\Base;

class Cancel extends Base
{
    public $permission = 'mxbackup_run';

    public function process()
    {
        $ok = $this->service()->platform()->runs()->requestCancel((int) $this->getProperty('id', 0));
        return $ok
            ? $this->success($this->modx->lexicon('mxbackup_stop_requested'))
            : $this->failure($this->modx->lexicon('mxbackup_no_active_run'));
    }
}
