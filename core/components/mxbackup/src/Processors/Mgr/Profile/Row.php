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
        ] + self::remoteRow(isset($profile['remote']) && is_array($profile['remote']) ? $profile['remote'] : []);
    }

    /**
     * Поля удалённого хранилища для грида и формы.
     *
     * ⚠️ Секрет наружу не отдаётся — только признак, что он задан. Форма
     * обращается с ним так же, как с паролем архива: пустое поле означает
     * «оставить как есть», а не «стереть».
     *
     * @param array<string, mixed> $remote
     * @return array<string, mixed>
     */
    private static function remoteRow(array $remote): array
    {
        $s3 = isset($remote['s3']) && is_array($remote['s3']) ? $remote['s3'] : [];
        $retention = isset($remote['retention']) && is_array($remote['retention']) ? $remote['retention'] : [];

        return [
            'remote_driver' => isset($remote['driver']) ? (string) $remote['driver'] : '',
            'remote_keep_local' => isset($remote['keep_local']) ? (int) $remote['keep_local'] : 2,
            'remote_retention_days' => isset($retention['days']) ? (int) $retention['days'] : 0,
            'remote_retention_count' => isset($retention['count']) ? (int) $retention['count'] : 0,
            'remote_s3_bucket' => isset($s3['bucket']) ? (string) $s3['bucket'] : '',
            'remote_s3_region' => isset($s3['region']) ? (string) $s3['region'] : '',
            'remote_s3_prefix' => isset($s3['prefix']) ? (string) $s3['prefix'] : '',
            'remote_s3_endpoint' => isset($s3['endpoint']) ? (string) $s3['endpoint'] : '',
            'remote_s3_storage_class' => isset($s3['storage_class']) ? (string) $s3['storage_class'] : '',
            'remote_s3_access_key' => isset($s3['access_key']) ? (string) $s3['access_key'] : '',
            'remote_s3_secret_set' => isset($s3['secret_key']) && (string) $s3['secret_key'] !== '',
        ];
    }
}
