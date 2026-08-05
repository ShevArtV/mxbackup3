<?php

namespace MxBackup\Processors\Mgr\Config;

use MxBackup\Processors\Mgr\Run\Create;

/**
 * Тот же запуск, но без публикации архива: показывает состав таблиц, число
 * обезличиваемых колонок и таблицы, которые будут очищены.
 */
class DryRun extends Create
{
    public function initialize()
    {
        $this->setProperty('dry_run', 1);

        return parent::initialize();
    }
}
