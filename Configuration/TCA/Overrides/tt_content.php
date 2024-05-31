<?php 
 
defined('TYPO3') or die();

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTcaSelectItemGroup(
	'tt_content',
	'CType',
	'customelement',
	'Custom Elements',
	'after:lists'
);


