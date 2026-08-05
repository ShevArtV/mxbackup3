<?php

namespace MxBackup\Processors\Mgr;

use MODX\Revolution\Processors\Processor;
use MxBackup\Core\Config\ProfileStore;
use MxBackup\Platform\Modx3\DatabaseAdapter;
use MxBackup\Platform\Modx3\ProfileRepository;
use MxBackup\Service;

/**
 * Общий предок процессоров админки: право доступа объявляется свойством, а
 * платформенные объекты берутся из сервиса пакета.
 */
abstract class Base extends Processor
{
    /** @var string Право MODX, без которого процессор не выполняется. */
    protected $permission = 'mxbackup_view';

    public function checkPermissions()
    {
        return $this->modx->hasPermission($this->permission);
    }

    public function getLanguageTopics()
    {
        return ['mxbackup:default'];
    }

    protected function service(): Service
    {
        return Service::fromModx($this->modx);
    }

    protected function store(): ProfileStore
    {
        $repository = $this->service()->platform()->profiles();

        /** @var ProfileRepository $repository */
        return $repository->getStore();
    }

    protected function database(): DatabaseAdapter
    {
        return $this->service()->platform()->database();
    }

    /**
     * Значения из формы приходят строками; браузер шлёт «on» для checkbox,
     * поэтому список допустимых истинных значений задаётся явно.
     */
    protected function boolean($value): bool
    {
        return in_array($value, [true, 1, '1', 'true', 'on'], true);
    }
}
