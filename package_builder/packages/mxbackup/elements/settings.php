<?php

return [
    'mxbackup.storage_path' => [
        'xtype' => 'textfield',
        'value' => '',
        'area' => 'mxbackup_general',
    ],
    'mxbackup.config_dir' => [
        'xtype' => 'textfield',
        'value' => '{core_path}config/mxbackup/profiles/',
        'area' => 'mxbackup_general',
    ],
    'mxbackup.config_path' => [
        'xtype' => 'textfield',
        'value' => '',
        'area' => 'mxbackup_general',
    ],
    'mxbackup.default_profile' => [
        'xtype' => 'textfield',
        'value' => 'prod',
        'area' => 'mxbackup_general',
    ],
    'mxbackup.archive_format' => [
        'xtype' => 'textfield',
        'value' => 'tar.gz',
        'area' => 'mxbackup_general',
    ],
    'mxbackup.mail_enabled' => [
        'xtype' => 'combo-boolean',
        'value' => '0',
        'area' => 'mxbackup_mail',
    ],
    'mxbackup.mail_to' => [
        'xtype' => 'textfield',
        'value' => '',
        'area' => 'mxbackup_mail',
    ],
    'mxbackup.mail_max_attachment_mb' => [
        'xtype' => 'numberfield',
        'value' => '10',
        'area' => 'mxbackup_mail',
    ],
    'mxbackup.retention_days' => [
        'xtype' => 'numberfield',
        'value' => '30',
        'area' => 'mxbackup_retention',
    ],
    'mxbackup.retention_count' => [
        'xtype' => 'numberfield',
        'value' => '10',
        'area' => 'mxbackup_retention',
    ],
    'mxbackup.lock_ttl_minutes' => [
        'xtype' => 'numberfield',
        'value' => '720',
        'area' => 'mxbackup_retention',
    ],
    'mxbackup.s3_multipart_threshold_mb' => [
        'xtype' => 'numberfield',
        'value' => '16',
        'area' => 'mxbackup_s3',
    ],
    'mxbackup.s3_part_size_mb' => [
        'xtype' => 'numberfield',
        'value' => '16',
        'area' => 'mxbackup_s3',
    ],
    'mxbackup.s3_part_retries' => [
        'xtype' => 'numberfield',
        'value' => '3',
        'area' => 'mxbackup_s3',
    ],
];
