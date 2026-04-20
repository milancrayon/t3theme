<?php
namespace Crayon\T3theme\DataProcessing;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

final class ThemeProcessor implements DataProcessorInterface
{
    /**
     * @param ContentObjectRenderer $cObj
     * @param array<string, mixed> $contentObjectConfiguration
     * @param array<string, mixed> $processorConfiguration
     * @param array<string, mixed> $processedData
     * @return array<string, mixed>
     */
    public function process(
        ContentObjectRenderer $cObj,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData
    ): array {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_t3theme_domain_model_themeconfig');

        $row = $queryBuilder
            ->select('*')
            ->from('tx_t3theme_domain_model_themeconfig')
            ->where(
                $queryBuilder->expr()->eq('hidden', 0),
                $queryBuilder->expr()->eq('deleted', 0)
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        $processedData['configHeader'] = $row && !empty($row['header'])
            ? json_decode($row['header'], true)
            : [];
        $processedData['configMenu'] = $row && !empty($row['menu'])
            ? json_decode($row['menu'], true)
            : [];
        $processedData['configLangMenu'] = $row && !empty($row['langm'])
            ? json_decode($row['langm'], true)
            : [];
        $processedData['configFooter'] = $row && !empty($row['footer'])
            ? json_decode($row['footer'], true)
            : [];

        return $processedData;
    }
}
