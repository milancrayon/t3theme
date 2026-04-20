<?php

declare(strict_types=1);

use Crayon\T3theme\Routing\Enhancer\T3Enhancer;

defined('TYPO3') or die();

$GLOBALS['TYPO3_CONF_VARS']['SYS']['routing']['enhancers']['T3Enhancer'] = T3Enhancer::class;
$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['cratelimit'] ??= [
    'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
    'backend' => \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class,
    'groups' => ['system'],
];
$GLOBALS['TYPO3_CONF_VARS']['BE']['defaultDocumentRoot'] = \TYPO3\CMS\Core\Core\Environment::getPublicPath();
