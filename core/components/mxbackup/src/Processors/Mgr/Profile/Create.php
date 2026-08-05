<?php

namespace MxBackup\Processors\Mgr\Profile;

use MxBackup\Core\Config\ProfileEditor;
use MxBackup\Processors\Mgr\Base;

class Create extends Base
{
    protected $permission = 'mxbackup_manage';

    public function process()
    {
        $name = trim((string) $this->getProperty('name'));
        if (!preg_match('/^[a-z0-9_-]+$/i', $name)) {
            return $this->failure($this->modx->lexicon('mxbackup_invalid_name'));
        }

        try {
            $store = $this->store();
            if ($store->find($name)) {
                return $this->failure($this->modx->lexicon('mxbackup_name_exists'));
            }

            $profile = (new ProfileEditor())->update([], $this->getProperties());
            $profile = array_merge($profile, [
                'name' => $name,
                'description' => trim((string) $this->getProperty('description', '')),
                'mode' => (string) $this->getProperty('mode', 'custom'),
                'active' => $this->boolean($this->getProperty('active')),
                'createdon' => time(),
                'editedon' => time(),
            ]);
            $profile = $store->save($profile);

            return $this->success('', Row::make($profile));
        } catch (\Throwable $e) {
            return $this->failure($e->getMessage());
        }
    }
}
