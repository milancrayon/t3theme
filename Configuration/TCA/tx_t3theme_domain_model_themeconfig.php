<?php
return [
    'ctrl' => [
        'title' => 'LLL:EXT:t3theme/Resources/Private/Language/locallang_t3theme.xlf:tx_t3theme_domain_model_themeconfig',
        'label' => 'header',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'versioningWS' => true,
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
        'searchFields' => 'data',
        'iconfile' => 'EXT:t3theme/Resources/Public/Icons/eicon.gif',
        'security' => [
			'ignorePageTypeRestriction' => true,
		],
    ],
    'types' => [
        '1' => ['showitem' => 'header,footer,cssjs,menu,langm,general, --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language, sys_language_uid, l10n_parent, l10n_diffsource, --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access, hidden, starttime, endtime'],
    ],
    'columns' => [
        'sys_language_uid' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'language',
              
            ],
        ],
        'l10n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'default' => 0,
                'items' => [
                    [ 'label' => '', 'value' => 0 ]
                ],
                'foreign_table' => 'tx_t3theme_domain_model_themeconfig',
                'foreign_table_where' => 'AND {#tx_t3theme_domain_model_themeconfig}.{#pid}=###CURRENT_PID### AND {#tx_t3theme_domain_model_themeconfig}.{#sys_language_uid} IN (-1,0)',
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

        'header' => [
            'exclude' => true,
            'label' => 'LLL:EXT:t3theme/Resources/Private/Language/locallang_t3theme.xlf:tx_t3theme_domain_model_themeconfig.header',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 15,
                'eval' => 'trim',
                'default' => ''
            ]
        ],
        'footer' => [
            'exclude' => true,
            'label' => 'LLL:EXT:t3theme/Resources/Private/Language/locallang_t3theme.xlf:tx_t3theme_domain_model_themeconfig.footer',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 15,
                'eval' => 'trim',
                'default' => ''
            ]
        ],
        'cssjs' => [
            'exclude' => true,
            'label' => 'LLL:EXT:t3theme/Resources/Private/Language/locallang_t3theme.xlf:tx_t3theme_domain_model_themeconfig.cssjs',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 15,
                'eval' => 'trim',
                'default' => ''
            ]
        ],
        'menu' => [
            'exclude' => true,
            'label' => 'LLL:EXT:t3theme/Resources/Private/Language/locallang_t3theme.xlf:tx_t3theme_domain_model_themeconfig.menu',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 15,
                'eval' => 'trim',
                'default' => ''
            ]
        ],
        'langm' => [
            'exclude' => true,
            'label' => 'LLL:EXT:t3theme/Resources/Private/Language/locallang_t3theme.xlf:tx_t3theme_domain_model_themeconfig.langm',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 15,
                'eval' => 'trim',
                'default' => ''
            ]
        ],
        'general' => [
            'exclude' => true,
            'label' => 'General',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 15,
                'eval' => 'trim',
                'default' => ''
            ]
        ],
    
    ],
];
