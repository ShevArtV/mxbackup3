<?php

namespace MxBackup\Processors\Mgr\Rule;

use MODX\Revolution\Processors\Processor;
use MxBackup\Core\Config\ProfileStore;

/**
 * Проверка пользовательского правила обезличивания.
 *
 * ⚠️ Действие приходит как `rule_action`, а не `action`: в коннекторе MODX
 * параметр `action` занят именем процессора, и «mask»/«truncate» было бы
 * прочитано как путь к процессору.
 */
final class Validator
{
    public static function data(Processor $processor, ProfileStore $store, $profileName, $excludeId = 0): array
    {
        $modx = $processor->modx;
        $profile = $store->find($profileName);
        if (!$profile) {
            throw new \InvalidArgumentException($modx->lexicon('mxbackup_profile_not_found'));
        }

        $targetType = (string) $processor->getProperty('target_type');
        $action = (string) $processor->getProperty('rule_action');
        $target = trim((string) $processor->getProperty('target'));
        $allowed = [
            'file' => ['include', 'exclude'],
            'directory' => ['include', 'exclude'],
            'table' => ['include', 'exclude', 'truncate'],
            'column' => ['mask', 'hide', 'hash', 'replace'],
            'json_path' => ['mask', 'hide', 'hash', 'replace'],
        ];

        if (!isset($allowed[$targetType])) {
            throw new \InvalidArgumentException($modx->lexicon('mxbackup_invalid_target_type'));
        }
        if (!in_array($action, $allowed[$targetType], true)) {
            throw new \InvalidArgumentException($modx->lexicon('mxbackup_invalid_action'));
        }
        if ($target === '') {
            throw new \InvalidArgumentException($modx->lexicon('field_required'));
        }
        if ($targetType === 'column' && substr_count($target, '.') < 1) {
            throw new \InvalidArgumentException($modx->lexicon('mxbackup_invalid_column_target'));
        }
        if ($targetType === 'json_path' && substr_count($target, '.') < 2) {
            throw new \InvalidArgumentException($modx->lexicon('mxbackup_invalid_json_target'));
        }
        if ($action === 'replace' && $processor->getProperty('value') === null) {
            throw new \InvalidArgumentException($modx->lexicon('field_required'));
        }
        try {
            $when = \MxBackup\Core\Masking\Rule::parseWhen($processor->getProperty('when', ''));
        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException($modx->lexicon('mxbackup_invalid_when'));
        }

        $rules = isset($profile['masking']['rules']) && is_array($profile['masking']['rules'])
            ? $profile['masking']['rules']
            : [];
        foreach ($rules as $existing) {
            if ((int) (isset($existing['id']) ? $existing['id'] : 0) === (int) $excludeId) {
                continue;
            }
            if ((string) $existing['target_type'] === $targetType && (string) $existing['target'] === $target) {
                throw new \InvalidArgumentException($modx->lexicon('mxbackup_rule_exists'));
            }
        }

        return [
            'target_type' => $targetType,
            'target' => $target,
            'action' => $action,
            'value' => $processor->getProperty('value'),
            'when' => $when,
            'priority' => (int) $processor->getProperty('priority', 100),
            'active' => in_array($processor->getProperty('active', 1), [true, 1, '1', 'true', 'on'], true),
        ];
    }
}
