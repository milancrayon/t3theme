<?php
use Crayon\T3theme\Controller\ElementsController;

return [
    't3theme_main' => [
        'labels' => 'LLL:EXT:t3theme/Resources/Private/Language/locallang_t3theme.xlf',
        'iconIdentifier' => 't3theme-be',
        'position' => ['after' => 'web'],
    ],
    't3theme' => [
        'parent' => 't3theme_main',
        'standalone' => true,
        'access' => 'user',
        'workspaces' => 'live',
        'path' => '/module/t3theme',
        'labels' => [
            'title' => 'LLL:EXT:t3theme/Resources/Private/Language/locallang_t3theme.xlf:config',
        ],
        'extensionName' => 'T3Theme',
        'iconIdentifier' => 't3theme-be',
        'controllerActions' => [
            ElementsController::class => [
                'index',
            ],
        ],
    ],
];
