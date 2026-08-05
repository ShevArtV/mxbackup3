<?php

namespace MxBackup\Processors\Mgr\Profile;

/**
 * Представление профиля для грида админки.
 *
 * Пароль шифрования наружу не отдаётся — только признак, что он задан.
 */
final class Row
{
    public static function make(array $profile): array
    {
        return [
            'id' => (string) $profile['name'],
            'name' => (string) $profile['name'],
            'description' => isset($profile['description']) ? (string) $profile['description'] : '',
            'mode' => isset($profile['mode']) ? (string) $profile['mode'] : 'custom',
            'active' => !empty($profile['active']),
            'format' => isset($profile['format']) ? (string) $profile['format'] : 'tar.gz',
            'encryption_enabled' => !empty($profile['encryption']['enabled']),
            'encryption_password_set' => isset($profile['encryption']['password'])
                && (string) $profile['encryption']['password'] !== '',
            'file_include' => implode("\n", isset($profile['files']['include']) ? $profile['files']['include'] : ['*']),
            'file_exclude' => implode("\n", isset($profile['files']['exclude']) ? $profile['files']['exclude'] : []),
            'standard_masking' => !empty($profile['masking']['standard']),
            'createdon' => isset($profile['createdon']) ? (int) $profile['createdon'] : 0,
            'editedon' => isset($profile['editedon']) ? (int) $profile['editedon'] : 0,
        ];
    }
}
