<?php

return [
    'mxbackup' => [
        'text' => 'mxbackup',
        'description' => 'mxbackup_menu_desc',
        'parent' => 'components',
        'action' => 'index',
        'namespace' => 'mxbackup',
        'icon' => '<i class="icon-database icon icon-large"></i>',
        'menuindex' => 0,
        // Права у пункта меню не задаются: доступ к странице и так проверяют
        // modManagerResponse::checkForMenuPermissions() и checkPermissions()
        // контроллера. Дублировать проверку в меню незачем.
        'permissions' => '',
    ],
];
