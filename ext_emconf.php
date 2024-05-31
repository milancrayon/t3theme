<?php
$EM_CONF['t3theme'] = [
    'title' => 'T3theme',
    'description' => 'Create Custom Element using T3theme',
    'constraints' => [
        'depends' => [
            'typo3' => '12.1.0-12.4.99',
            'nnrestapi' => '2.0.4',
            'container' => '2.3.6',
            'news' => '11.4.1'
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
    'autoload' => [
        'psr-4' => [ 
            "Crayon\\T3theme\\"=> "Classes",
        ],
    ],
    'category' => 'templates',
    'uploadfolder' => 0,
    'createDirs' => '',
    'author' => 'T3Element',
    'author_email' => 'info@t3theme.com',
    'author_company' => 'Crayonwebstudio private limited',
    'state' => 'stable',
    'version' => '1.0.2',
    'clearcacheonload' => 1,
];
