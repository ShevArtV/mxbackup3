<?php

namespace MxBackup\Processors\Mgr\Rule;

use MxBackup\Processors\Mgr\Base;

class Remove extends Base
{
    public $permission = 'mxbackup_manage';

    public function process()
    {
        try {
            $store = $this->store();
            $profileName = trim((string) $this->getProperty('profile_id'));
            if (!$store->removeRule($profileName, (int) $this->getProperty('id'))) {
                return $this->failure($this->modx->lexicon('mxbackup_rule_not_found'));
            }

            return $this->success();
        } catch (\Throwable $e) {
            return $this->failure($e->getMessage());
        }
    }
}
