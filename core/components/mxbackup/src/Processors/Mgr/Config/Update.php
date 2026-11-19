<?php

namespace MxBackup\Processors\Mgr\Config;

use MODX\Revolution\modSystemSetting;
use MxBackup\Processors\Mgr\Base;

class Update extends Base
{
    public $permission = 'mxbackup_manage';

    public function process()
    {
        $properties = $this->getProperties();
        $xtypes = [
            'mxbackup.mail_enabled' => 'combo-boolean',
            'mxbackup.mail_max_attachment_mb' => 'numberfield',
            'mxbackup.retention_days' => 'numberfield',
            'mxbackup.retention_count' => 'numberfield',
            'mxbackup.lock_ttl_minutes' => 'numberfield',
            'mxbackup.s3_multipart_threshold_mb' => 'numberfield',
            'mxbackup.s3_part_size_mb' => 'numberfield',
            'mxbackup.s3_part_retries' => 'numberfield',
        ];

        foreach (Get::FIELDS as $field => $key) {
            // Отсутствующее поле пропускается, а не очищается: форма может
            // прислать только часть настроек.
            if (array_key_exists($field, $properties)) {
                $value = $properties[$field];
            } else {
                $legacyField = str_replace('.', '_', $key);
                if (!array_key_exists($legacyField, $properties)) {
                    continue;
                }
                $value = $properties[$legacyField];
            }

            $setting = $this->modx->getObject(modSystemSetting::class, ['key' => $key]);
            if (!$setting) {
                $setting = $this->modx->newObject(modSystemSetting::class);
                $setting->set('key', $key);
                $setting->set('namespace', 'mxbackup');
                $setting->set('xtype', isset($xtypes[$key]) ? $xtypes[$key] : 'textfield');
            }
            $setting->set('value', (string) $value);
            $setting->set('editedon', time());
            $setting->save();
        }

        $this->modx->getCacheManager()->refresh(['system_settings' => []]);

        return $this->success($this->modx->lexicon('mxbackup_settings_saved'));
    }
}
