<?php

declare(strict_types=1);

namespace Crayon\T3theme\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Connection;

/**
 * @extensionScannerIgnoreFile
 */
final class T3repeatViewHelper extends AbstractViewHelper
{
    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        $this->registerArgument('uid', 'int', 'Uid not valid', true);
        $this->registerArgument('CType', 'string', 'CType not valid', true);
        $this->registerArgument('key', 'string', 'key not valid', true);
    }

    /**
     * @return array<int|string, mixed>
     */
    public function render(): array
    {
        $uid = $this->arguments['uid'];
        $CType = str_replace('t3theme_', '', $this->arguments['CType']);
        $fld_key = $this->arguments['key'];

        // var_dump($CType);



        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_t3theme_domain_model_elements');
        $queryBuilder->select("*");
        $queryBuilder->from('tx_t3theme_domain_model_elements');
        $_where[] = $queryBuilder->expr()->like('data', $queryBuilder->createNamedParameter('%' . $queryBuilder->escapeLikeWildcards($CType) . '%'));
        $queryBuilder->where(...$_where);
        $result = $queryBuilder->executeQuery();
        $_edata = $result->fetchAllAssociative();
 
        if ($_edata !== []) {
            $element = json_decode($_edata[0]['data']);
            $repeaters = [];
            foreach ($element->columns as $clmn) {
                if ($clmn->id == "idrepeating" && $clmn->field->key == $fld_key) {
                    $images = [];
                    foreach ($clmn->field->formArray as $itm) {
                        if ($itm->fieldType == "idfile") {
                            $images[] = $itm->key;
                        }
                    }
                    $table = "tx_" . $clmn->field->key . "_item";
                    $r_query = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
                    $r_query->select("*");
                    $r_query->from($table);
                    $r_where[] = $r_query->expr()->eq('tt_content', $r_query->createNamedParameter($this->arguments['uid'], Connection::PARAM_INT));
                    $r_query->where(...$r_where);
                    $_result = $r_query->executeQuery();
                    $__records = $_result->fetchAllAssociative();
                    $_finalthR = [];
                    foreach ($__records as $rv) {
                        if (sizeof($images) > 0) {
                            foreach ($images as $iky) {
                                $files = GeneralUtility::makeInstance(FileRepository::class)->findByRelation($table, $iky, $rv['uid']);
                                $_imgs = [];
                                if (sizeof($files) > 0) {
                                    foreach ($files as $CType => $value) {
                                        $file = $value->getCombinedIdentifier();
                                        $_imgs[] = $file;
                                    }
                                }
                                $rv[$iky] = $_imgs;
                            }
                        }
                        $_finalthR[] = $rv;
                    }
                    $repeaters[] = $_finalthR;

                }
            }
            return $repeaters;
        }
        return [];
    }
}
