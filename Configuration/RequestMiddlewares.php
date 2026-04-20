<?php

return [
	'frontend' => [

		't3theme/resolver' => [
			'target' => \Crayon\T3theme\Middleware\PageResolver::class,
			'before' => [
				'typo3/cms-frontend/content-length-headers',
			],
			'after' => [
				'typo3/cms-frontend/shortcut-and-mountpoint-redirect',
			],
		],
		'browser-language-redirect' => [
			'target' => \Crayon\T3theme\Middleware\LanguageDetection::class,
			'before' => [
				'typo3/cms-frontend/base-redirect-resolver',
			],
			'after' => [
				'typo3/cms-frontend/site',
			],
		],
	]
];