<?php
use Crayon\T3theme\Controller\ElementsController;

return [
    't3theme' => [
        'parent' => 'web',
        'position' => ['after' => 'web_info'],
        'access' => 'user',
        'workspaces' => 'live',
        'path' => '/module/t3theme',
        'labels' => 'LLL:EXT:t3theme/Resources/Private/Language/locallang_t3theme.xlf',
        'icon'   => 'EXT:t3theme/Resources/Public/Icons/ic.png',
        'extensionName' => 'T3theme',
        'controllerActions' => [
            ElementsController::class => [
                'index',
            ],
        ],
    ],
];
