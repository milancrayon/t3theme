<?php
return [
  'cratelimit' => [
    'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
     'backend' => \TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend::class,
    'groups' => ['system'],
    'options' => [], 
  ],
];
