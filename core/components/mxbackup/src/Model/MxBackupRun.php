<?php
namespace MxBackup\Model;

use xPDO\xPDO;

/**
 * Class MxBackupRun
 *
 * @property string $profile
 * @property string $mode
 * @property string $status
 * @property string $storage_path
 * @property string $archive_path
 * @property integer $archive_size
 * @property string $archive_checksum
 * @property array $manifest_json
 * @property array $report_json
 * @property boolean $email_sent
 * @property string $error
 * @property integer $startedon
 * @property integer $completedon
 *
 * @package MxBackup\Model
 */
class MxBackupRun extends \xPDO\Om\xPDOSimpleObject
{
}
