<?php

declare(strict_types=1);

namespace Crayon\T3theme\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class ImgsourceViewHelper extends AbstractViewHelper
{
	protected $escapeOutput = false;
	public function initializeArguments(): void
	{
		$this->registerArgument('uid', 'int', 'Uid not valid', true);
		$this->registerArgument('field', 'string', 'Field not valid', true);
	}

	/**
	 * @return array<int|string, mixed>
	 */
	public function render(): array
	{
		$uid = $this->arguments['uid'];
		$field = $this->arguments['field'];

		$files = GeneralUtility::makeInstance(FileRepository::class)
			->findByRelation('tt_content', $field, $uid);
		$_imgs = [];
		if (sizeof($files) > 0) {
			foreach ($files as $key => $value) {
				$file = $value->getCombinedIdentifier();
				$_imgs[] = $file;
			}
		}
		return $_imgs;
	}
}