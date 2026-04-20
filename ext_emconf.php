<?php
$EM_CONF['t3theme'] = [
    'title' => 'T3theme',
    'description' => 'Create Custom Element using T3theme',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-14.4.99',
            'container' => '^3.1.12',
            't3themeextend' => '1.0.0'
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
    'autoload' => [
        'psr-4' => [
            "Crayon\\T3theme\\" => "Classes",
        ],
    ],
    'category' => 'templates',
    'uploadfolder' => 0,
    'createDirs' => '',
    'author' => 'T3Element',
    'author_email' => 'info@t3theme.com',
    'author_company' => 'Crayonwebstudio private limited',
    'state' => 'stable',
    'version' => '1.0.0',
    'clearcacheonload' => 1,
];
