<?php

namespace MxBackup\Processors\Mgr\Profile\Tables;

use MxBackup\Core\Config\ProfileEditor;
use MxBackup\Processors\Mgr\Base;

/**
 * Состав таблиц профиля. Массовые операции при непустом поиске меняют только
 * найденные таблицы, при пустом — все.
 */
class Update extends Base
{
    protected $permission = 'mxbackup_manage';

    public function process()
    {
        $store = $this->store();
        $profileName = (string) $this->getProperty('profile_id');
        $config = $store->find($profileName);
        if (!$config) {
            return $this->failure($this->modx->lexicon('mxbackup_profile_not_found'));
        }

        $editor = new ProfileEditor();
        $available = $this->database()->listTables();
        $selected = $editor->selection($config, $available);
        $selectionMode = $editor->selectionMode($config);
        $operation = (string) $this->getProperty('operation', 'replace');

        if ($operation === 'toggle') {
            $table = (string) $this->getProperty('table');
            if (!in_array($table, $available, true)) {
                return $this->failure($this->modx->lexicon('mxbackup_table_not_found'));
            }
            $included = $this->boolean($this->getProperty('included'));
            $selected = array_values(array_diff($selected, [$table]));
            if ($included) {
                $selected[] = $table;
            }
        } elseif ($operation === 'set_all') {
            $matching = $editor->filterTables($available, $this->getProperty('query', ''));
            if ($this->boolean($this->getProperty('included'))) {
                $selected = array_values(array_unique(array_merge($selected, $matching)));
            } else {
                $selected = array_values(array_diff($selected, $matching));
            }
        } elseif ($operation === 'mode') {
            $selectionMode = (string) $this->getProperty('selection_mode', $selectionMode);
        } elseif ($operation === 'replace') {
            $selected = json_decode((string) $this->getProperty('tables', '[]'), true);
            if (!is_array($selected)) {
                return $this->failure($this->modx->lexicon('mxbackup_invalid_table_selection'));
            }
            $selectionMode = (string) $this->getProperty('selection_mode', $selectionMode);
        } else {
            return $this->failure($this->modx->lexicon('mxbackup_invalid_table_selection'));
        }

        try {
            $config = $editor->applyTableSelection($config, $available, $selected, $selectionMode);
        } catch (\InvalidArgumentException $e) {
            return $this->failure($e->getMessage());
        }

        $config['editedon'] = time();
        try {
            $store->save($config, $profileName);
        } catch (\Throwable $e) {
            return $this->failure($e->getMessage());
        }

        return $this->success($this->modx->lexicon('mxbackup_tables_saved'));
    }
}
