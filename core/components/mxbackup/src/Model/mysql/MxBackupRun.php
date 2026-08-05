<?php
namespace MxBackup\Model\mysql;

use xPDO\xPDO;

class MxBackupRun extends \MxBackup\Model\MxBackupRun
{

    public static $metaMap = array (
        'package' => 'MxBackup\\Model',
        'version' => '3.0',
        'table' => 'mxbackup_run',
        'extends' => 'xPDO\\Om\\xPDOSimpleObject',
        'tableMeta' => 
        array (
            'engine' => 'InnoDB',
        ),
        'fields' => 
        array (
            'profile' => '',
            'mode' => 'prod',
            'status' => 'running',
            'storage_path' => NULL,
            'archive_path' => NULL,
            'archive_size' => 0,
            'archive_checksum' => '',
            'manifest_json' => NULL,
            'report_json' => NULL,
            'email_sent' => 0,
            'error' => NULL,
            'startedon' => 0,
            'completedon' => 0,
        ),
        'fieldMeta' => 
        array (
            'profile' => 
            array (
                'dbtype' => 'varchar',
                'precision' => '100',
                'phptype' => 'string',
                'null' => false,
                'default' => '',
                'index' => 'index',
            ),
            'mode' => 
            array (
                'dbtype' => 'varchar',
                'precision' => '20',
                'phptype' => 'string',
                'null' => false,
                'default' => 'prod',
                'index' => 'index',
            ),
            'status' => 
            array (
                'dbtype' => 'varchar',
                'precision' => '20',
                'phptype' => 'string',
                'null' => false,
                'default' => 'running',
                'index' => 'index',
            ),
            'storage_path' => 
            array (
                'dbtype' => 'text',
                'phptype' => 'string',
                'null' => true,
            ),
            'archive_path' => 
            array (
                'dbtype' => 'text',
                'phptype' => 'string',
                'null' => true,
            ),
            'archive_size' => 
            array (
                'dbtype' => 'integer',
                'precision' => '20',
                'attributes' => 'unsigned',
                'phptype' => 'integer',
                'null' => false,
                'default' => 0,
            ),
            'archive_checksum' => 
            array (
                'dbtype' => 'char',
                'precision' => '64',
                'phptype' => 'string',
                'null' => false,
                'default' => '',
            ),
            'manifest_json' => 
            array (
                'dbtype' => 'mediumtext',
                'phptype' => 'json',
                'null' => true,
            ),
            'report_json' => 
            array (
                'dbtype' => 'mediumtext',
                'phptype' => 'json',
                'null' => true,
            ),
            'email_sent' => 
            array (
                'dbtype' => 'tinyint',
                'precision' => '1',
                'attributes' => 'unsigned',
                'phptype' => 'boolean',
                'null' => false,
                'default' => 0,
            ),
            'error' => 
            array (
                'dbtype' => 'mediumtext',
                'phptype' => 'string',
                'null' => true,
            ),
            'startedon' => 
            array (
                'dbtype' => 'integer',
                'precision' => '20',
                'attributes' => 'unsigned',
                'phptype' => 'integer',
                'null' => false,
                'default' => 0,
                'index' => 'index',
            ),
            'completedon' => 
            array (
                'dbtype' => 'integer',
                'precision' => '20',
                'attributes' => 'unsigned',
                'phptype' => 'integer',
                'null' => false,
                'default' => 0,
            ),
        ),
        'indexes' => 
        array (
            'profile' => 
            array (
                'alias' => 'profile',
                'primary' => false,
                'unique' => false,
                'type' => 'BTREE',
                'columns' => 
                array (
                    'profile' => 
                    array (
                        'length' => '',
                        'collation' => 'A',
                        'null' => false,
                    ),
                ),
            ),
            'status' => 
            array (
                'alias' => 'status',
                'primary' => false,
                'unique' => false,
                'type' => 'BTREE',
                'columns' => 
                array (
                    'status' => 
                    array (
                        'length' => '',
                        'collation' => 'A',
                        'null' => false,
                    ),
                ),
            ),
            'startedon' => 
            array (
                'alias' => 'startedon',
                'primary' => false,
                'unique' => false,
                'type' => 'BTREE',
                'columns' => 
                array (
                    'startedon' => 
                    array (
                        'length' => '',
                        'collation' => 'A',
                        'null' => false,
                    ),
                ),
            ),
        ),
    );

}
