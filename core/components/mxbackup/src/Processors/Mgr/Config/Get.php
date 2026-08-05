<?php

namespace MxBackup\Processors\Mgr\Config;

use MODX\Revolution\modSystemSetting;
use MxBackup\Processors\Mgr\Base;

class Get extends Base
{
    /**
     * Ключи системных настроек содержат точки, а PHP заменяет их на
     * подчёркивания в именах POST-полей. Поэтому форма работает с безопасными
     * именами, а маппинг на mxbackup.* живёт здесь и в Update.
     */
    public const FIELDS = [
        'storage_path' => 'mxbackup.storage_path',
        'config_dir' => 'mxbackup.config_dir',
        'config_path' => 'mxbackup.config_path',
        'default_profile' => 'mxbackup.default_profile',
        'archive_format' => 'mxbackup.archive_format',
        'mail_enabled' => 'mxbackup.mail_enabled',
        'mail_to' => 'mxbackup.mail_to',
        'mail_max_attachment_mb' => 'mxbackup.mail_max_attachment_mb',
        'retention_days' => 'mxbackup.retention_days',
        'retention_count' => 'mxbackup.retention_count',
        'lock_ttl_minutes' => 'mxbackup.lock_ttl_minutes',
    ];

    public function process()
    {
        $values = [];
        foreach (self::FIELDS as $field => $key) {
            $values[$field] = $this->rawValue($key);
        }

        return $this->success('', $values);
    }

    /**
     * Значение настройки как оно записано, без разворачивания плейсхолдеров.
     *
     * getOption() подставляет {core_path}, и форма сохранила бы обратно уже
     * абсолютный путь — профиль перестал бы переезжать между сайтами.
     */
    private function rawValue(string $key)
    {
        $setting = $this->modx->getObject(modSystemSetting::class, ['key' => $key]);

        return $setting ? (string) $setting->get('value') : (string) $this->modx->getOption($key, null, '');
    }
}
