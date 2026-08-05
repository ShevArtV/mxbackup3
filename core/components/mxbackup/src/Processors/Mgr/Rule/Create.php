<?php

namespace MxBackup\Processors\Mgr\Rule;

use MxBackup\Processors\Mgr\Base;

class Create extends Base
{
    public $permission = 'mxbackup_manage';

    public function process()
    {
        try {
            $store = $this->store();
            $profileName = trim((string) $this->getProperty('profile_id'));
            $rule = Validator::data($this, $store, $profileName);
            $rule = $store->addRule($profileName, $rule);

            return $this->success('', $rule);
        } catch (\Throwable $e) {
            return $this->failure($e->getMessage());
        }
    }
}
