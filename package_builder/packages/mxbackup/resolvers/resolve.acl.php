<?php

/**
 * Resolver: права и политика доступа mxBackup.
 *
 * Создаёт идемпотентно шаблон mxbackupTemplate, права и политику
 * mxbackupDefault. Никакой группе политика не назначается — доступ выдаётся
 * вручную через Access Controls.
 *
 * Право `load` обязано быть и в шаблоне, и в политике: проверка идёт через
 * modNamespace (modAccessibleObject), и без `load` non-sudo пользователь не
 * получит даже namespace-объект.
 *
 * @var \xPDO\Transport\xPDOTransport $transport
 * @var array $options
 */

use MODX\Revolution\modAccessPermission;
use MODX\Revolution\modAccessPolicy;
use MODX\Revolution\modAccessPolicyTemplate;
use MODX\Revolution\modX;
use xPDO\Transport\xPDOTransport;

if (!$transport->xpdo) {
    return true;
}

/** @var modX $modx */
$modx = $transport->xpdo;
$action = $options[xPDOTransport::PACKAGE_ACTION] ?? '';

if ($action === xPDOTransport::ACTION_UNINSTALL) {
    // Права и политику не удаляем: к ним привязаны выданные доступы групп.
    return true;
}

$permissions = [
    'load' => 'Загрузка namespace mxBackup (обязательно для любой проверки прав)',
    'mxbackup_view' => 'Просмотр mxBackup',
    'mxbackup_manage' => 'Управление профилями и правилами',
    'mxbackup_run' => 'Запуск резервного копирования',
    'mxbackup_restore' => 'Восстановление из резервной копии',
];

/** @var modAccessPolicyTemplate|null $template */
$template = $modx->getObject(modAccessPolicyTemplate::class, ['name' => 'mxbackupTemplate']);
if (!$template) {
    $template = $modx->newObject(modAccessPolicyTemplate::class);
    $template->fromArray([
        'template_group' => 1,
        'name' => 'mxbackupTemplate',
        'description' => 'Права mxBackup',
        'lexicon' => 'permissions',
    ], '', true, true);

    if (!$template->save()) {
        $modx->log(modX::LOG_LEVEL_ERROR, '[mxbackup] Не удалось создать шаблон политики mxbackupTemplate.');

        return true;
    }
    $modx->log(modX::LOG_LEVEL_INFO, '[mxbackup] Создан шаблон политики mxbackupTemplate.');
}

$templateId = (int) $template->get('id');

foreach ($permissions as $name => $description) {
    $exists = $modx->getObject(modAccessPermission::class, [
        'template' => $templateId,
        'name' => $name,
    ]);
    if ($exists) {
        continue;
    }

    $permission = $modx->newObject(modAccessPermission::class);
    $permission->fromArray([
        'template' => $templateId,
        'name' => $name,
        'description' => $description,
        'value' => 1,
    ], '', true, true);
    $permission->save();
    $modx->log(modX::LOG_LEVEL_INFO, '[mxbackup] Добавлено право: ' . $name);
}

$data = [];
foreach (array_keys($permissions) as $name) {
    $data[$name] = true;
}

/** @var modAccessPolicy|null $policy */
$policy = $modx->getObject(modAccessPolicy::class, ['name' => 'mxbackupDefault']);
if (!$policy) {
    $policy = $modx->newObject(modAccessPolicy::class);
    $policy->fromArray([
        'name' => 'mxbackupDefault',
        'description' => 'Полный доступ к mxBackup, включая восстановление.',
        'parent' => 0,
        'template' => $templateId,
        'class' => '',
        'lexicon' => 'permissions',
    ], '', true, true);
    $policy->set('data', $data);
    $policy->save();
    $modx->log(modX::LOG_LEVEL_INFO, '[mxbackup] Создана политика mxbackupDefault.');
} else {
    // Апгрейд: дописываем только новые права, не трогая осознанно снятые.
    $current = $policy->get('data');
    if (!is_array($current)) {
        $current = [];
    }

    $changed = false;
    foreach ($data as $name => $value) {
        if (!array_key_exists($name, $current)) {
            $current[$name] = $value;
            $changed = true;
        }
    }

    if ($changed) {
        $policy->set('data', $current);
        $policy->save();
        $modx->log(modX::LOG_LEVEL_INFO, '[mxbackup] Политика mxbackupDefault дополнена новыми правами.');
    }
}

return true;
