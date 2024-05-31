<?php
return [
    'ctrl' => [
        'title' => 'License',
        'label' => 'c_lc',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'versioningWS' => true,
        'label_alt_force' => true,
        'origUid' => 't3_origuid',
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
        'searchFields' => 'email,c_lc,status,is_verify,log,version,last_verify',
        'iconfile' => 'EXT:t3theme/Resources/Public/Icons/eicon.gif',
        'security' => [
			'ignorePageTypeRestriction' => true,
		],
    ],
    'types' => [
        '1' => ['showitem' => 'c_lc,email,status,is_verify,log,version,last_verify, --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.access, starttime, endtime'],
    ],
    'columns' => [
        'sys_language_uid' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'language',

            ],
        ],
        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'hidden' => [
            'config' => [
                'type' => 'check',
                'items' => [
                    ['label' => 'Disable'],
                ],
            ]
        ],
        'starttime' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.starttime',
            'config' => [
                'type' => 'input',
                'renderType' => 'datetime',
                'eval' => 'datetime,int',
                'default' => 0,
                'behaviour' => [
                    'allowLanguageSynchronization' => true
                ]
            ],
        ],
        'endtime' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.endtime',
            'config' => [
                'type' => 'input',
                'renderType' => 'datetime',
                'eval' => 'datetime,int',
                'default' => 0,
                'range' => [
                    'upper' => mktime(0, 0, 0, 1, 1, 2038)
                ],
                'behaviour' => [
                    'allowLanguageSynchronization' => true
                ]
            ],
        ],

        'email' => [
            'exclude' => true,
            'label' => 'Email',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim'
            ],
        ],

        'c_lc' => [
            'exclude' => true,
            'label' => 'License',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim'
            ],
        ],

        'status' => [
            'exclude' => true,
            'label' => 'status',
            'config' => [
                'type' => 'radio',
                'items' => [
                    ['label' => 'No', 'value' => 0],
                    ['label' => 'Yes', 'value' => 1],
                ],
            ],
        ],

        'is_verify' => [
            'exclude' => true,
            'label' => 'Is verify',
            'config' => [
                'type' => 'radio',
                'items' => [
                    ['label' => 'No', 'value' => 0],
                    ['label' => 'Yes', 'value' => 1],
                ],
            ],
        ],

        'log' => [
            'exclude' => true,
            'label' => 'Log',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim'
            ],
        ],

        'version' => [
            'exclude' => true,
            'label' => 'Version',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim'
            ],
        ],

        'last_verify' => [
            'exclude' => true,
            'label' => 'Last verify',
            'config' => [
                'type' => 'radio',
                'items' => [
                    ['label' => 'No', 'value' => 0],
                    ['label' => 'Yes', 'value' => 1],
                ],
            ],
        ],


    ],
];
