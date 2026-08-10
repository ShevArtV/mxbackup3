<?php

namespace MxBackup\Platform\Modx3;

use MODX\Revolution\modX;
use MxBackup\Core\Contract\RunRepositoryInterface;
use MxBackup\Model\MxBackupRun;

final class RunRepository implements RunRepositoryInterface
{
    private $modx;

    public function __construct(modX $modx)
    {
        $this->modx = $modx;
    }

    public function start(array $data)
    {
        /** @var MxBackupRun $run */
        $run = $this->modx->newObject(MxBackupRun::class);
        $run->fromArray($data, '', true, true);
        return $run->save() ? (int)$run->get('id') : 0;
    }

    /**
     * Финализация пишется подготовленным запросом по whitelist полей: случай,
     * когда архив создан, а история осталась в статусе running, разбирался
     * отдельно — молча игнорировать неудачу здесь нельзя, вызывающий код
     * считает её ошибкой запуска.
     */
    public function finish($id, array $data)
    {
        if (!$id) {
            return false;
        }

        $allowed = [
            'status', 'archive_path', 'archive_size', 'archive_checksum',
            'manifest_json', 'report_json', 'email_sent', 'error', 'completedon',
        ];
        $jsonFields = ['manifest_json', 'report_json'];
        $sets = [];
        $values = [];

        foreach ($allowed as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }
            $value = $data[$field];
            if (in_array($field, $jsonFields, true) && $value !== null) {
                $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                if ($value === false) {
                    return false;
                }
            } elseif ($field === 'email_sent') {
                $value = $value ? 1 : 0;
            }
            $sets[] = '`' . $field . '` = ?';
            $values[] = $value;
        }

        if (!$sets) {
            return false;
        }

        $values[] = (int)$id;
        $statement = $this->modx->prepare(
            'UPDATE ' . $this->modx->getTableName(MxBackupRun::class)
            . ' SET ' . implode(', ', $sets) . ' WHERE `id` = ?'
        );

        return $statement && $statement->execute($values);
    }

    public function requestCancel($id = 0)
    {
        $table = $this->modx->getTableName(MxBackupRun::class);
        $select = $this->modx->prepare(
            'SELECT `id`, `storage_path` FROM ' . $table . ' WHERE `status` = \'running\''
            . ($id ? ' AND `id` = ?' : ' ORDER BY `id` DESC LIMIT 1')
        );
        if (!$select || !$select->execute($id ? [(int) $id] : []) || !($run = $select->fetch(\PDO::FETCH_ASSOC))) {
            return false;
        }
        $id = (int) $run['id'];
        $statement = $this->modx->prepare(
            'UPDATE ' . $table . ' SET `status` = \'cancelling\' WHERE `id` = ? AND `status` = \'running\''
        );
        if (!$statement || !$statement->execute([$id]) || $statement->rowCount() < 1) {
            return false;
        }
        $marker = rtrim((string) $run['storage_path'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '.mxbackup-cancel-' . $id;
        if (@file_put_contents($marker, (string) time(), LOCK_EX) === false) {
            $rollback = $this->modx->prepare('UPDATE ' . $table . ' SET `status` = \'running\' WHERE `id` = ? AND `status` = \'cancelling\'');
            if ($rollback) $rollback->execute([$id]);
            return false;
        }
        @chmod($marker, 0600);
        return true;
    }

    public function isCancellationRequested($id)
    {
        $statement = $this->modx->prepare(
            'SELECT `status` FROM ' . $this->modx->getTableName(MxBackupRun::class) . ' WHERE `id` = ?'
        );
        return $statement && $statement->execute([(int) $id])
            && (string) $statement->fetchColumn() === 'cancelling';
    }
}
