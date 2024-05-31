<?php 

defined('TYPO3') or die(); 
use \TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
 
call_user_func(function () { 
    ExtensionManagementUtility::registerPageTSConfigFile(
        "t3theme",
        'Configuration/TsConfig/Page/All.tsconfig',
        "t3theme"
    );
});
