<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;

return [
    't3theme' => [
        'provider' => BitmapIconProvider::class,
        'source' => 'EXT:t3theme/Resources/Public/Icons/ic.png',
        'spinning' => true,
    ],
];