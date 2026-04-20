<?php
declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;

$beimage = 'icon.png';
if (GeneralUtility::makeInstance(Typo3Version::class)->getMajorVersion() >= 14) {
    $beimage = "icon-14.svg";
}

return [
    't3theme' => [
        'provider' => BitmapIconProvider::class,
        'source' => 'EXT:t3theme/Resources/Public/Icons/icon.png',
        'spinning' => false,
    ],
    't3theme-be' => [
        'provider' => BitmapIconProvider::class,
        'source' => 'EXT:t3theme/Resources/Public/Icons/' . $beimage,
        'spinning' => false,
    ],
];