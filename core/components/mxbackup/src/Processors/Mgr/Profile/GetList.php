<?php

namespace MxBackup\Processors\Mgr\Profile;

use MxBackup\Processors\Mgr\Base;

class GetList extends Base
{
    public function process()
    {
        try {
            $rows = [];
            foreach ($this->store()->all() as $profile) {
                $rows[] = Row::make($profile);
            }
            $total = count($rows);
            $start = max(0, (int) $this->getProperty('start', 0));
            $limit = max(0, (int) $this->getProperty('limit', 20));
            if ($limit > 0) {
                $rows = array_slice($rows, $start, $limit);
            }

            return $this->outputArray($rows, $total);
        } catch (\Throwable $e) {
            return $this->failure($e->getMessage());
        }
    }
}
