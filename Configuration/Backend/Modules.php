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
        'extensionName' => 'T3Theme',
        'iconIdentifier' => 't3theme-be',
        'controllerActions' => [
            ElementsController::class => [
                'index',
            ],
        ],
    ],
];
