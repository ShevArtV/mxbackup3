<?php

namespace MxBackup\Processors\Mgr\Config;

use MxBackup\Core\Config\ConfigValidator;
use MxBackup\Processors\Mgr\Base;

class Validate extends Base
{
    public function process()
    {
        try {
            $config = $this->service()->config(['profile' => (string) $this->getProperty('profile', 'prod')]);
            $errors = (new ConfigValidator())->validate($config);

            return $errors
                ? $this->failure(implode('; ', $errors))
                : $this->success($this->modx->lexicon('mxbackup_config_valid'));
        } catch (\Throwable $e) {
            return $this->failure($e->getMessage());
        }
    }
}
