<?php
declare(strict_types=1);
namespace Crayon\T3theme\Domain\Repository;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Repository;
use TYPO3\CMS\Core\Database\Connection;



/**
 * @extends \TYPO3\CMS\Extbase\Persistence\Repository<\Crayon\T3theme\Domain\Model\Elements>
 */
class ElementsRepository extends Repository
{  
    /**
     * @param array<string, mixed> $data
     * @return bool
     */
    public function elementOperations(array $data): bool{
        
        $i = 0;
        if(sizeof($data['tt_content']) > 0){ 
            foreach($data['tt_content'] as $ttcontent){ 
                
                $addColumn = "ALTER TABLE tt_content
							ADD ".$ttcontent['val'];
                $modifycolumn  = "ALTER TABLE tt_content
                                MODIFY COLUMN ".$ttcontent['val'];

                $checkforColumn = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tt_content')->executeStatement("SHOW COLUMNS FROM `tt_content` LIKE '".$ttcontent['key']."'");
                
                if($checkforColumn){
                    try{
                        GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tt_content')->executeStatement($modifycolumn);
                    }
                    catch(\Exception $e){
                        return false;
                    }
                }else{
                    try{
                        GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tt_content')->executeStatement($addColumn);
                    }
                    catch(\Exception $e){
                        return false;
                    }
                }
                if($i == (sizeof($data['tt_content'])-1)){
                    if(isset($data['repeater']) && sizeof($data['repeater']) > 0){
                        return $this->repeaterElementContent($data['repeater']);
                    }else{
                        return true;
                    }
                }
                $i++;
            }
        }else{
            if(isset($data['repeater']) && sizeof($data['repeater']) > 0){
                return $this->repeaterElementContent($data['repeater']);
            }else{
                return true;
            }
        }
        return true;
    }

    /**
     * @param array<int|string, array<string, mixed>> $data
     * @return bool
     */
    public function repeaterElementContent(array $data): bool{
        if(sizeof($data) > 0){
            $j=0;
            $size = sizeof($data);
            foreach($data as $repeater){
                
                $data = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($repeater['key'])->createSchemaManager()->tablesExist([$repeater['key']]);
                if($data){
                    GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($repeater['key'])->executeStatement('Drop Table '.$repeater['key']);
                }
                try{
                    GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($repeater['key'])->executeStatement($repeater['val']);
                    if($j == ($size - 1)){
                        return true;
                    }
                    $j++;
                }
                catch(\Exception $r){
                    return false;
                }
            }
        }else{
            return true; 
        }
        return true;
    }
    /**
     * @param array<string, mixed> $element
     * @return bool
     */
    public function elementRemove(array $element): bool{
        try{
            if(isset($element['tt_content']) && sizeof($element['tt_content']) > 0){
                foreach($element['tt_content'] as $tt){
                    $checkforColumn = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tt_content')->executeStatement("SHOW COLUMNS FROM `tt_content` LIKE '".$tt['key']."'");
                    if($checkforColumn){
                        GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tt_content')->executeStatement("ALTER TABLE tt_content DROP COLUMN ".$tt['key'].";");
                    }
                }
            }
            if(isset($element['repeater']) && sizeof($element['repeater']) > 0){
                $s = [];$i=0;
                foreach($element['repeater'] as $repeater){
                    $data = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getConnectionForTable($repeater['key'])
                    ->createSchemaManager()
                    ->tablesExist([$repeater['key']]);

                    if($data){
			            $s[$i] = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($repeater['key'])->executeStatement($repeater['data']);	
                    }
                    $i++;
                }
                if(sizeof($s) == sizeof($element['repeater'])){
                    return true;
                }
            }else{
                return true;
            }
        }
        catch (\Exception $e){
            return false;
        }
        return true;
    }
    /**
     * @param array<string, mixed> $element
     * @return bool
     */
    public function elementUpdate(array $element): bool{
        $tt_content = $element['tt_content'];
        $repeater = $element['repeater'];
        if(isset($tt_content['newfield']) && sizeof($tt_content['newfield']) > 0){
            foreach($tt_content['newfield'] as $nFld){
                $addColumn = "ALTER TABLE tt_content
							ADD ".$nFld['val'];
                $modifycolumn  = "ALTER TABLE tt_content
                                MODIFY COLUMN ".$nFld['val'];
                $checkforColumn = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tt_content')->executeStatement("SHOW COLUMNS FROM `tt_content` LIKE '".$nFld['key']."'");
                if($checkforColumn){
                    try{GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tt_content')->executeStatement($modifycolumn);}catch(\Exception $e){return false;}
                }else{
                    try{GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tt_content')->executeStatement($addColumn);}catch(\Exception $e){return false;}
                }
            }
        }
        if(isset($tt_content['removefields']) && sizeof($tt_content['removefields']) > 0){
            foreach($tt_content['removefields'] as $rFld){
                $checkforColumn = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tt_content')->executeStatement("SHOW COLUMNS FROM `tt_content` LIKE '".$rFld['key']."'");
                if($checkforColumn){
                    try{GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tt_content')->executeStatement('ALTER TABLE tt_content DROP COLUMN '.$rFld['key']);}catch(\Exception $e){return false;}
                }
            }
        }
        if(isset($repeater['remove']) && sizeof($repeater['remove']) > 0){
            foreach($repeater['remove'] as $rmvv){
                $data = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable($rmvv)
                ->createSchemaManager()
                ->tablesExist([$rmvv]);

                if($data){
                    try{GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($rmvv)->executeStatement('DROP TABLE '.$rmvv);}catch(\Exception $r){return false;}
                }
            }
        }
        if(isset($repeater['newD']) && sizeof($repeater['newD']) > 0){
            foreach($repeater['newD'] as $ndata){
                $data = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($ndata['key'])->createSchemaManager()->tablesExist([$ndata['key']]);
                if($data){
                    try{GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($ndata['key'])->executeStatement('Drop Table '.$ndata['key']);}catch(\Exception $r){return false;}
                }
                try{GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($ndata['key'])->executeStatement($ndata['val']);}catch(\Exception $r){return false;}
                
            }
        }
        if(isset($repeater['updation']) && sizeof($repeater['updation']) > 0){
            foreach ($repeater['updation'] as $up) {
                $table = $up['table'];
                $fields = $up['data'];
                if(sizeof($fields) > 0){
                    foreach($fields as $fl){
                        $checkforTable = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table)->createSchemaManager()->tablesExist([$table]);
                        if($checkforTable){
                            $checkforColumn = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table)->executeStatement("SHOW COLUMNS FROM ".$table." LIKE '".$fl['key']."'");
                            if($checkforColumn){
                                try{GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table)->executeStatement("ALTER TABLE ".$table." MODIFY COLUMN ".$fl['val']);}catch(\Exception $e){return false;}
                            }else{
                                try{GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table)->executeStatement("ALTER TABLE ".$table." ADD COLUMN ".$fl['val']);}catch(\Exception $e){return false;}
                            }
                        }
                    }
                }
            }
        }
        return true;
    }


    /**
     * @param int $uid
     * @return array<string, mixed>|null
     */
    public function getContentDetail(int $uid): ?array{
        $tt_contentQr = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        $result = $tt_contentQr->select("*")->from('tt_content')->where(
            $tt_contentQr->expr()->eq('uid', $tt_contentQr->createNamedParameter($uid, Connection::PARAM_INT))
        )->executeQuery();
        return $result->fetchAssociative();
    }

   
}