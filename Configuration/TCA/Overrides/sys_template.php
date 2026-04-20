<?php

declare(strict_types=1);

defined('TYPO3') or die();
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;


ExtensionManagementUtility::addStaticFile(
    't3theme',
    'Configuration/TypoScript',
    'T3theme'
);