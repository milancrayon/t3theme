<?php

namespace Crayon\T3theme\Api;


use Nng\Nnrestapi\Annotations as Api;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Crayon\T3theme\Domain\Repository\ElementsRepository;
use Crayon\T3theme\Domain\Repository\ThemeconfigRepository; 
use TYPO3\CMS\Core\Database\ConnectionPool;
use \TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Core\Environment;
use GeorgRinger\News\Domain\Repository\NewsRepository;


/**
 * @Api\Endpoint()
 */
class Import extends \Nng\Nnrestapi\Api\AbstractApi
{
    /**
     * @var ElementsRepository
     */
    private $elementsRepository = null;
    /**
     * @var ThemeconfigRepository
     */
    private $themeconfigRepository = null;


    /**
     * Constructor
     * Inject the . 
     * Ignore storagePid.
     * 
     * @return void
     */
    public function __construct()
    {
        $this->elementsRepository = \nn\t3::injectClass(ElementsRepository::class);
        $this->themeconfigRepository = \nn\t3::injectClass(ThemeconfigRepository::class);
        $this->newsRepository = \nn\t3::injectClass(NewsRepository::class);
        \nn\t3::Db()->ignoreEnableFields($this->elementsRepository);
        \nn\t3::Db()->ignoreEnableFields($this->themeconfigRepository);
    }

    /**
     * @Api\Upload("default")
     * @Api\Route("POST /import/element")
     * @Api\Access("public")
     * @return array
     */
    public function elementAction()
    {
        if (sizeof($this->request->getUploadedFiles()) > 0) {
            $uploadedd = $this->request->getUploadedFiles();
            $ext_pub = 'EXT:t3theme/';
            $_extpath = GeneralUtility::getFileAbsFileName($ext_pub);
            foreach ($uploadedd as $key => $value) {
                $src = $value;
                $dir = $_extpath . 'Resources/Public/imports';
                $srcFileName = $dir . '/' . pathinfo($src->getClientFilename(), PATHINFO_BASENAME);
                if ($src->getClientMediaType() == "application/json") {
                    if (!is_string($src) && is_a($src, \TYPO3\CMS\Core\Http\UploadedFile::class)) {
                        if ($stream = $src->getStream()) {
                            $handle = fopen($srcFileName, 'wb+');
                            if ($handle === false)
                                return false;
                            $stream->rewind();
                            while (!$stream->eof()) {
                                $bytes = $stream->read(4096);
                                fwrite($handle, $bytes);
                            }
                            fclose($handle);
                        }
                    }
                    $fileContent = file_get_contents(
                        \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($srcFileName)
                    );
                    $uploadData = json_decode($fileContent, TRUE);


                    if ($uploadData) {
                        if (isset($uploadData['elements'])) {
                            $elements = $uploadData['elements'];
                            if ($elements && sizeof($elements) > 0) {
                                foreach ($elements as $rec) {
                                    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_t3theme_domain_model_elements');
                                    $queryBuilder->select("*");
                                    $queryBuilder->from('tx_t3theme_domain_model_elements');
                                    $_where[] = $queryBuilder->expr()->like('data', $queryBuilder->createNamedParameter('%' . $queryBuilder->escapeLikeWildcards(key($rec)) . '%'));
                                    $queryBuilder->where(...$_where);
                                    $result = $queryBuilder->executeQuery();
                                    $existing = $result->fetchAllAssociative();

                                    if ($existing && sizeof($existing) > 0) {
                                        $update_uid = $existing[0]['uid'];
                                        $_uq = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_t3theme_domain_model_elements');
                                        $_uq->update('tx_t3theme_domain_model_elements')->where($_uq->expr()->eq('uid', $_uq->createNamedParameter($update_uid)));
                                        $_uq->set('data', json_encode($rec[key($rec)])); 
                                        $result = $_uq->executeStatement();
                                    } else {
                                        $_elem = GeneralUtility::makeInstance(\Crayon\T3theme\Domain\Model\Elements::class);
                                        $_elem->setData(json_encode($rec[key($rec)]));
                                        $res = $this->elementsRepository->add($_elem);
                                        /** @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager */
                                        $persistenceManager = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager::class);
                                        $persistenceManager->persistAll();
                                    }
                                }
                            }
                        }
                        if (isset($uploadData['files'])) {
                            $files = $uploadData['files'];
                            if (isset($files['tsconfig'])) {
                                $ext_tsconfig = $_extpath . 'Configuration/TsConfig/Page/Mod/Wizards/Elements/';
                                if ($files['tsconfig'] && sizeof($files['tsconfig']) > 0) {
                                    foreach ($files['tsconfig'] as $file) {

                                        $tsFile = $ext_tsconfig . key($file) . '.tsconfig';
                                        if ($ts_fll = fopen($tsFile, "w+")) {

                                            if (fwrite($ts_fll, json_decode(current($file)))) {
                                                fclose($ts_fll);
                                            }
                                        }
                                    }
                                }
                            }
                            if (isset($files['typoscript'])) {
                                $ext_typoscript = $_extpath . 'Configuration/TypoScript/Elements/';
                                if ($files['typoscript'] && sizeof($files['typoscript']) > 0) {
                                    foreach ($files['typoscript'] as $file) {
                                        $tsFile = $ext_typoscript . key($file) . '.typoscript';
                                        if ($ts_fll = fopen($tsFile, "w+")) {
                                            if (fwrite($ts_fll, json_decode(current($file)))) {
                                                fclose($ts_fll);
                                            }
                                        }
                                    }
                                }
                            }
                            if (isset($files['tt_content'])) {
                                $tt_content = $_extpath . 'Configuration/TCA/Overrides/Elements/';
                                if ($files['tt_content'] && sizeof($files['tt_content']) > 0) {
                                    foreach ($files['tt_content'] as $file) {
                                        $tsFile = $tt_content . key($file) . '.php';
                                        if ($ts_fll = fopen($tsFile, "w+")) {
                                            if (fwrite($ts_fll, json_decode(current($file)))) {
                                                fclose($ts_fll);
                                            }
                                        }
                                    }
                                }
                            }
                            if (isset($files['tca'])) {
                                $tt_tca = $_extpath . 'Configuration/TCA/';
                                if ($files['tca'] && sizeof($files['tca']) > 0) {
                                    foreach ($files['tca'] as $file) {
                                        $tsFile = $tt_tca . key($file) . '.php';
                                        if ($ts_fll = fopen($tsFile, "w+")) {
                                            if (fwrite($ts_fll, json_decode(current($file)))) {
                                                fclose($ts_fll);
                                            }
                                        }
                                    }
                                }
                            }
                            if (isset($files['templates'])) {
                                $tt_templates = $_extpath . 'Resources/Private/Templates/ContentElements/';
                                if ($files['templates'] && sizeof($files['templates']) > 0) {
                                    foreach ($files['templates'] as $file) {
                                        $tsFile = $tt_templates . key($file) . '.html';
                                        if ($ts_fll = fopen($tsFile, "w+")) {
                                            if (fwrite($ts_fll, json_decode(current($file)))) {
                                                fclose($ts_fll);
                                            }
                                        }
                                    }
                                }
                            }
                            if (isset($files['preview'])) {
                                $tt_preview = $_extpath . 'Resources/Private/Templates/Preview/';
                                if ($files['preview'] && sizeof($files['preview']) > 0) {
                                    foreach ($files['preview'] as $file) {
                                        $tsFile = $tt_preview . key($file) . '.html';
                                        if ($ts_fll = fopen($tsFile, "w+")) {
                                            if (fwrite($ts_fll, json_decode(current($file)))) {
                                                fclose($ts_fll);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        if (isset($uploadData['updates'])) {
                            $updates = $uploadData['updates'];
                            if (isset($updates['tt_content'])) {
                                $__ttcontetn = $_extpath . 'Configuration/TCA/Overrides/tt_content.php';
                                $_existing = file_get_contents($__ttcontetn);
                                if ($updates['tt_content'] && sizeof($updates['tt_content']) > 0) {
                                    $append = "";
                                    foreach ($updates['tt_content'] as $item) {
                                        if (strpos($_existing, $item)) {
                                        } else {
                                            $append = $append . "\ninclude 'Elements/" . $item . ".php';";
                                        }
                                    }
                                }
                                if ($append != "") {
                                    $newttcont = $_existing . $append;
                                    if ($ts_fll = fopen($__ttcontetn, "wb+")) {
                                        if (fwrite($ts_fll, $newttcont)) {
                                            fclose($ts_fll);
                                        }
                                    }
                                }
                            }
                            if (isset($updates['ext_tables_sql'])) {
                                $sqlData = $updates['ext_tables_sql'];
                                $_sql_ext = $_extpath . 'ext_tables.sql';
                                $_sqllda = file_get_contents($_sql_ext);

                                $sql_append = "";

                                if (isset($sqlData['tt_content'])) {
                                    $tt_contentData = "";
                                    foreach ($sqlData['tt_content'] as $keyi => $defi) {
                                        if (strpos($_sqllda, $keyi)) {
                                        } else {
                                            $tt_contentData = $tt_contentData . "\n\t" . $keyi . " " . $defi . ",";
                                        }
                                    }
                                    if ($tt_contentData != "") {
                                        $sql_append = $sql_append . "\nCREATE TABLE tt_content(";
                                        $sql_append = $sql_append . $tt_contentData;
                                        $sql_append = $sql_append . "\n);";
                                    }
                                }

                                if (isset($sqlData['tables'])) {
                                    $rpttable = "";
                                    foreach ($sqlData['tables'] as $tabl => $tdatil) {
                                        if ($tdatil && sizeof($tdatil) > 0) {
                                            $inner = "";
                                            foreach ($tdatil as $rfld => $rfval) {
                                                if (strpos($_sqllda, $rfld)) {
                                                } else {
                                                    $inner = $inner . "\n\t" . $rfld . " " . $rfval . ",";
                                                }
                                            }
                                            if ($inner != "") {
                                                $rpttable = $rpttable . "\nCREATE TABLE " . $tabl . "(\n\ttt_content int(11) unsigned DEFAULT '0',";
                                                $rpttable = $rpttable . $inner;
                                                $rpttable = $rpttable . "\n);";
                                            }
                                        }
                                    }
                                    if ($rpttable != "") {
                                        $sql_append = $sql_append . $rpttable;
                                    }
                                }
                                if ($sql_append != "") {
                                    $ext_swll = $_sqllda . $sql_append;
                                    if ($ts_fll = fopen($_sql_ext, "wb+")) {
                                        if (fwrite($ts_fll, $ext_swll)) {
                                            fclose($ts_fll);
                                        }
                                    }
                                }
                            }
                        }
                        return ["message" => "Import Successfully!!"];
                    } else {
                        return ["error" => "Invlaid Json File !!"];
                    }
                } else {
                    return ["error" => "Invalid json file!!"];
                }
            }
        }
    }


    /**
     * @Api\Upload("default")
     * @Api\Route("POST /import/pages")
     * @Api\Access("public")
     * @return array
     */
    public function pagesAction()
    {
        if (sizeof($this->request->getUploadedFiles()) > 0) {
            $uploadedd = $this->request->getUploadedFiles();
            $ext_pub = 'EXT:t3theme/';
            $_extpath = GeneralUtility::getFileAbsFileName($ext_pub);
            foreach ($uploadedd as $key => $value) {
                $src = $value;
                $dir = $_extpath . 'Resources/Public/imports';
                $srcFileName = $dir . '/' . pathinfo($src->getClientFilename(), PATHINFO_BASENAME);
                if ($src->getClientMediaType() == "application/json") {
                    if (!is_string($src) && is_a($src, \TYPO3\CMS\Core\Http\UploadedFile::class)) {
                        if ($stream = $src->getStream()) {
                            $handle = fopen($srcFileName, 'wb+');
                            if ($handle === false)
                                return false;
                            $stream->rewind();
                            while (!$stream->eof()) {
                                $bytes = $stream->read(4096);
                                fwrite($handle, $bytes);
                            }
                            fclose($handle);
                        }
                    }
                    $fileContent = file_get_contents(
                        \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($srcFileName)
                    );
                    $uploadData = json_decode($fileContent, TRUE);


                    if ($uploadData && sizeof($uploadData) > 0) {
                        $error_messages = [];
                        if (isset($uploadData['pages'])) {
                            $main_pid = $this->request->getMvcRequest()->getAttribute('site')->getConfiguration()['rootPageId'];
                            if (sizeof($uploadData['pages']) > 0) {
                                foreach ($uploadData['pages'] as $pg_detail) {

                                    $error_messages[] = $this->addPage($pg_detail, $main_pid);
                                }
                            }
                        }
                        if (isset($uploadData['tables'])) {
                            foreach ($uploadData['tables'] as $tkey => $tdatas) {
                                if (sizeof($tdatas) > 0) {
                                    $Fdatas = [];
                                    foreach ($tdatas as $trwo) {
                                        $_add = 0;
                                        if (isset($trwo['keyy'])) {
                                            $keyy = $trwo['keyy'];
                                            $find = [];
                                            $find[] = ["key" => $keyy, "val" => rtrim($trwo[$keyy], '/'), "exp" => "eq"];
                                            $_recEds = $this->getReferenceUid($tkey, $find);
                                            if ($_recEds && sizeof($_recEds) > 0) {
                                                $find = [];
                                                foreach ($_recEds as $ntg) {
                                                    $find[] = ["key" => "uid", "val" => $ntg['uid'], "exp" => "eq"];
                                                }
                                                $remove = $this->removeEntries($tkey, $find);
                                                $_add = 1;
                                            } else {
                                                $_add = 1;
                                            }

                                            unset($trwo['keyy']);
                                        } else {
                                            $_add = 1;
                                        }
                                        if ($_add == 1) {
                                            if (isset($trwo['pid'])) {
                                                if (gettype($trwo['pid']) == 'string') {
                                                    $trwo['pid'] = $this->getPageUidBySlug($trwo['pid']);
                                                }
                                            }
                                            $Fdatas[] = $trwo;
                                        }
                                    }
                                    $error_messages[] = $this->addtablesDataa($Fdatas, $tkey, null, null);
                                }
                            }
                        }
                        if (isset($uploadData['sys_category'])) {
                            $finl = [];
                            foreach ($uploadData['sys_category'] as $categg) {

                                $find = [];
                                $find[] = ["key" => "slug", "val" => rtrim($categg['slug'], '/'), "exp" => "eq"];
                                $_recEds = $this->getReferenceUid('sys_category', $find);
                                if ($_recEds && sizeof($_recEds) > 0) {
                                } else {
                                    $finl[] = $categg;
                                }
                            }
                            $error_messages[] = $this->addtablesDataa($finl, 'sys_category', 'childs', 'parent');
                        }
                        if (isset($uploadData['news'])) {
                            if (sizeof($uploadData['news']) > 0) {
                                foreach ($uploadData['news'] as $blog) {
                                    $b_pid = null;
                                    if (isset($blog['pid'])) {
                                        if (gettype($blog['pid']) == 'string') {
                                            $b_pid = $this->getPageUidBySlug(rtrim($blog['pid'], '/'));
                                        }
                                    }
                                    if ($b_pid) {
                                        $blog['pid'] = $b_pid;
                                    }

                                    $find = [];
                                    $find[] = ["key" => "path_segment", "val" => rtrim($blog['path_segment'], '/'), "exp" => "eq"];
                                    $_recEds = $this->getReferenceUid('tx_news_domain_model_news', $find);
                                    if ($_recEds && sizeof($_recEds) > 0) {
                                        $remove = $this->removeEntries('tx_news_domain_model_news', $find);
                                    }

                                    $_upp = isset($blog['uploads']) ? $blog['uploads'] : null;
                                    $_link_related = isset($blog['link_related']) ? $blog['link_related'] : null;
                                    $category = isset($blog['category']) ? $blog['category'] : null;
                                    $related_tags = isset($blog['related_tags']) ? $blog['related_tags'] : null;
                                    $__tt_content = isset($blog['tt_content']) ? $blog['tt_content'] : null;
                                    if (isset($blog['uploads'])) {
                                        unset($blog['uploads']);
                                    }
                                    if (isset($blog['link_related'])) {
                                        unset($blog['link_related']);
                                    }
                                    if (isset($blog['category'])) {
                                        unset($blog['category']);
                                    }
                                    if (isset($blog['related_tags'])) {
                                        unset($blog['related_tags']);
                                    }
                                    if (isset($blog['tt_content'])) {
                                        unset($blog['tt_content']);
                                    }

                                    $blog['tstamp'] = time();
                                    $blog['crdate'] = time();

                                    
                                    try {
                                        $addedBlog = \nn\t3::Db()->save("tx_news_domain_model_news", $blog);
                                    } catch (\Throwable $th) {
                                        
                                        $error_messages[] = $th->getMessage();
                                    }

                                    if($addedBlog['uid']){
                                        if ($_upp && sizeof($_upp) > 0) {
                                            foreach ($_upp as $ufl) {
                                                $flpath = $ufl['path'];
                                                $extra = [];
                                                if (isset($ufl['showinpreview'])) {
                                                    $extra[] = ["field" => "showinpreview", "value" => $ufl['showinpreview']];
                                                }
                                                $iref = $this->createReferenceImage($flpath, $addedBlog['uid'], $addedBlog['pid'], 'tx_news_domain_model_news', $ufl['field'], $extra);
                                                if ($iref['status'] != 1) {
                                                    $error_messages[] = $iref['msg'];
                                                }
                                            }
                                        }
    
                                        if ($_link_related && sizeof($_link_related) > 0) {
                                            foreach ($_link_related as $rflnk) {
                                                try {
                                                    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_news_domain_model_link');
                                                    $affectedRows = $queryBuilder
                                                        ->insert('tx_news_domain_model_link')
                                                        ->values([
                                                            "tstamp" => time(),
                                                            "crdate" => time(),
                                                            "parent" => (int) $addedBlog['uid'],
                                                            "pid" => $addedBlog['pid'],
                                                            "sys_language_uid" => $rflnk["sys_language_uid"],
                                                            "description" => $rflnk["description"],
                                                            "title" => $rflnk["title"],
                                                            "uri" => $rflnk["uri"]
                                                        ])
                                                        ->executeStatement();
                                                } catch (\Throwable $th) {
                                                    $error_messages[] = $th->getMessage();
                                                }
                                            }
                                        }
    
                                        if ($category && sizeof($category) > 0) {
                                            foreach ($category as $cat) {
                                                $find = [];
                                                $find[] = ["key" => "slug", "val" => rtrim($cat['slug'], '/'), "exp" => "eq"];
                                                $_recEds = $this->getReferenceUid('sys_category', $find);
                                                $cid = null;
    
                                                if ($_recEds && sizeof($_recEds) > 0) {
                                                    foreach ($_recEds as $c_daaa) {
                                                        $cid = $c_daaa['uid'];
                                                    }
                                                }
                                                if ($cid) { 
                                                    $_catRec['uid_local'] = $cid;
                                                    $_catRec['uid_foreign'] = (int) $addedBlog['uid'];
                                                    $_catRec['tablenames'] = 'tx_news_domain_model_news';
                                                    $_catRec['fieldname'] = 'categories';
                                                    try {
                                                        $find = [];
                                                        $find[] = ["key" => "uid_local", "val" => $cid, "exp" => "eq"];
                                                        $find[] = ["key" => "uid_foreign", "val" => $addedBlog['uid'], "exp" => "eq"];
                                                        $find[] = ["key" => "tablenames", "val" => "tx_news_domain_model_news", "exp" => "like"];
                                                        $find[] = ["key" => "fieldname", "val" => "categories", "exp" => "like"];
                                                        $remove = $this->removeEntries("sys_category_record_mm", $find);
                                                        $cat_ref = \nn\t3::Db()->save("sys_category_record_mm", $_catRec);
                                                    } catch (\Throwable $th) {
    
                                                        $error_messages[] = $th->getMessage();
                                                    }
                                                }
                                            }
                                        }
    
                                        if ($related_tags && sizeof($related_tags) > 0) {
                                            foreach ($related_tags as $_tag) {
                                                $find = [];
                                                $find[] = ["key" => "slug", "val" => rtrim($_tag, '/'), "exp" => "eq"];
                                                $_recEds = $this->getReferenceUid('tx_news_domain_model_tag', $find);
                                                $tagid = null;
    
                                                if ($_recEds && sizeof($_recEds) > 0) {
                                                    foreach ($_recEds as $c_daaa) {
                                                        $tagid = $c_daaa['uid'];
                                                    }
                                                }
                                                if ($tagid) {
                                                    $tg_rc['uid_foreign'] = $tagid;
                                                    $tg_rc['uid_local'] = (int) $addedBlog['uid'];
                                                    try {
                                                        $insertedTag = \nn\t3::Db()->save("tx_news_domain_model_news_tag_mm", $tg_rc);
                                                    } catch (\Throwable $th) {
    
                                                        $error_messages[] = $th->getMessage();
                                                    }
                                                }
                                            }
                                        }
    
                                        if ($__tt_content && sizeof($__tt_content) > 0) {
                                            $_pidd = $addedBlog['pid'];
                                            $j=1;
                                            foreach ($__tt_content as $pslug => $_records) {
                                                $_records['tx_news_related_news'] = $addedBlog['uid'];
                                                if (isset($_records['pid'])) {
                                                    if (gettype($_records['pid']) == 'string') {
                                                        $_pidd = $this->getPageUidBySlug(rtrim($_records['pid'], '/'));
                                                    }
                                                }
                                                try {
                                                    $_records['sorting'] = $j;
                                                    $error_messages[] = $this->addPageElements($_records, $_pidd, 'tt_content');
                                                } catch (\Throwable $th) { 
                                                    $error_messages[] = $th->getMessage();
                                                }
                                                $j++;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        if (isset($uploadData['related_news'])) {
                            foreach ($uploadData['related_news'] as $rnw) {
                                if (sizeof($rnw) > 0) {
                                    foreach ($rnw as $fk => $frc) {

                                        $find = [];
                                        $find[] = ["key" => "path_segment", "val" => rtrim($fk, '/'), "exp" => "eq"];
                                        $_recEds = $this->getReferenceUid('tx_news_domain_model_news', $find);
                                        $base_id = null;
                                        if ($_recEds && sizeof($_recEds) > 0) {
                                            foreach ($_recEds as $rval) {
                                                $base_id = $rval['uid'];
                                            }
                                        }
                                        if ($base_id) {
                                            if (sizeof($frc) > 0) {
                                                foreach ($frc as $bth) {

                                                    $find = [];
                                                    $find[] = ["key" => "path_segment", "val" => rtrim($bth, '/'), "exp" => "eq"];
                                                    $__recEds = $this->getReferenceUid('tx_news_domain_model_news', $find);
                                                    $_for = null;
                                                    if ($__recEds && sizeof($__recEds) > 0) {
                                                        foreach ($__recEds as $rval) {
                                                            $_for = $rval['uid'];
                                                        }
                                                    }
                                                    if ($_for) {
                                                        try {
                                                            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_news_domain_model_news_related_mm');
                                                            $result = $queryBuilder->select('uid_foreign')->from('tx_news_domain_model_news_related_mm')->where(
                                                                $queryBuilder->expr()->eq('uid_foreign', $queryBuilder->createNamedParameter($base_id, Connection::PARAM_INT)),
                                                                $queryBuilder->expr()->eq('uid_local', $queryBuilder->createNamedParameter($_for, Connection::PARAM_INT))
                                                            )->executeStatement();

                                                            if ($result == 0) {
                                                                $ref['uid_foreign'] = $base_id;
                                                                $ref['uid_local'] = $_for;
                                                                $rrnews = \nn\t3::Db()->save("tx_news_domain_model_news_related_mm", $ref);
                                                            }
                                                        } catch (\Throwable $th) {

                                                            $error_messages[] = $th->getMessage();
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        if (isset($uploadData['tt_content'])) {
                            foreach ($uploadData['tt_content'] as $pslug => $_records) {
                                $_pidd = $this->getPageUidBySlug(rtrim($pslug, '/'));
                                $error_messages[] = $this->removePageContent($_pidd);
                                if ($_pidd) {
                                    if (sizeof($_records) > 0) {
                                        foreach ($_records as $ky => $record) {
                                            if (isset($record['pid'])) {
                                                if (gettype($record['pid']) == 'string') {
                                                    $_pidd = $this->getPageUidBySlug($record['pid']);
                                                }
                                            }
                                            $record['sorting'] = $ky;
                                            $error_messages[] = $this->addPageElements($record, $_pidd, 'tt_content');
                                        }
                                    }
                                } else {
                                    return ["messages" => "Please import page or add valid Page!!"];
                                }
                            }
                        }

                        $final_error = [];
                        if (sizeof($error_messages)) {
                            foreach ($error_messages as $error) {
                                $er = $this->getErrorLEvel($error);
                                if ($er) {
                                    foreach ($er as $__e) {
                                        $final_error[] = $__e;
                                    }
                                }
                            }
                        }
                        if (sizeof($final_error) > 0) {
                            $final_error = array_unique($final_error);
                        } else {
                            $final_error = null;
                        }
                        return ["error" => $final_error, "message" => "Import Successfully !!"];
                    } else {
                        return ["error" => "Invalid JSon File!!"];
                    }
                }
            }
        }
    }


    /**
     * @Api\Upload("default")
     * @Api\Route("POST /import/theme")
     * @Api\Access("public")
     * @return array
     */
    public function themeAction()
    {
        if (sizeof($this->request->getUploadedFiles()) > 0) {
            $uploadedd = $this->request->getUploadedFiles();
            $ext_pub = 'EXT:t3theme/';
            $_extpath = GeneralUtility::getFileAbsFileName($ext_pub);
            foreach ($uploadedd as $key => $value) {
                $src = $value;
                $dir = $_extpath . 'Resources/Public/imports';
                $srcFileName = $dir . '/' . pathinfo($src->getClientFilename(), PATHINFO_BASENAME);
                if ($src->getClientMediaType() == "application/json") {
                    if (!is_string($src) && is_a($src, \TYPO3\CMS\Core\Http\UploadedFile::class)) {
                        if ($stream = $src->getStream()) {
                            $handle = fopen($srcFileName, 'wb+');
                            if ($handle === false)
                                return false;
                            $stream->rewind();
                            while (!$stream->eof()) {
                                $bytes = $stream->read(4096);
                                fwrite($handle, $bytes);
                            }
                            fclose($handle);
                        }
                    }
                    $fileContent = file_get_contents(
                        \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($srcFileName)
                    );
                    $uploadData = json_decode($fileContent, TRUE);
                    if (isset($uploadData['theme'])) {
                        $theme = $uploadData['theme'];
                        $_menu = isset($theme['menu']) ? $theme['menu'] : null;
                        $_header = isset($theme['header']) ? $theme['header'] : null;
                        $_footer = isset($theme['footer']) ? $theme['footer'] : null;
                        $_cssjs = isset($theme['cssjs']) ? $theme['cssjs'] : null;
                        $_langm = isset($theme['langm']) ? $theme['langm'] : null;
                        $_general = isset($theme['general']) ? $theme['general'] : null;

                        if ($_menu) {
                            if (isset($_menu['menu'])) {
                                $menus = $_menu['menu'];
                                if (sizeof($menus) > 0) {
                                    $fmenu = [];
                                    foreach ($menus as $mi) {
                                        $_pid = $this->getPageUidBySlug(rtrim($mi['uid'], '/'));
                                        if ($_pid) {
                                            $mi['uid'] = $_pid;
                                        } else {
                                            $mi['uid'] = 1;
                                        }
                                        if (sizeof($mi['child']) > 0) {
                                            $ref = [];
                                            foreach ($mi['child'] as $cd) {
                                                $__pid = $this->getPageUidBySlug(rtrim($cd['uid'], '/'));
                                                if ($__pid) {
                                                    $cd['uid'] = $__pid;
                                                } else {
                                                    $cd['uid'] = 1;
                                                }
                                                if (sizeof($cd['child']) > 0) {
                                                    $_ref = [];
                                                    foreach ($cd['child'] as $_tcd) {
                                                        $t__pid = $this->getPageUidBySlug(rtrim($_tcd['uid'], '/'));
                                                        if ($t__pid) {
                                                            $_tcd['uid'] = $t__pid;
                                                        } else {
                                                            $_tcd['uid'] = 1;
                                                        }
                                                        $_ref[] = $_tcd;
                                                    }
                                                    $cd['child'] = $_ref;
                                                }
                                                $ref[] = $cd;
                                            }
                                            $mi['child'] = $ref;
                                        }
                                        $fmenu[] = $mi;
                                    }
                                    $_menu['menu'] = $fmenu;
                                }
                            }
                        }

                        if ($_general) {
                            if (isset($_general["p404"])) {
                                $_p404 = $this->getPageUidBySlug(rtrim($_general["p404"], '/'));
                                if ($_p404) {
                                    $_general["p404"] = $_p404;
                                } else {
                                    $_general["p404"] = 0;
                                }
                            }
                        }
                        if ($_footer) {
                            if (isset($_footer["footerpage"])) {
                                $_footerpage = $this->getPageUidBySlug(rtrim($_footer["footerpage"], '/'));
                                if ($_footerpage) {
                                    $_footer["footerpage"] = $_footerpage;
                                } else {
                                    $_footer["footerpage"] = 0;
                                }
                            }
                        }
                        if ($_header) {
                            if (isset($_header["header_top"])) {
                                $_header_top = $this->getPageUidBySlug(rtrim($_header["header_top"], '/'));
                                if ($_header_top) {
                                    $_header["header_top"] = $_header_top;
                                } else {
                                    $_header["header_top"] = 0;
                                }
                            }
                            if (isset($_header["header_bottom"])) {
                                $_header_bottom = $this->getPageUidBySlug(rtrim($_header["header_bottom"], '/'));
                                if ($_header_bottom) {
                                    $_header["header_bottom"] = $_header_bottom;
                                } else {
                                    $_header["header_bottom"] = 0;
                                }
                            }
                            if (isset($_header["logo"])) {
                                $path = $_header["logo"]["extpath"];
                                $_header["logo"]['public'] = $this->getPublicUrlofFile($path);
                            }
                            if (isset($_header["favicon"])) {
                                $path = $_header["favicon"]["extpath"];
                                $_header["favicon"]['public'] = $this->getPublicUrlofFile($path);
                            }
                            if (isset($_header["head_css"])) {
                                if (sizeof($_header["head_css"]) > 0) {
                                    $_nhcss = [];
                                    foreach ($_header["head_css"] as $h_value) {
                                        $path = $h_value["extpath"];
                                        $h_value['public'] = $this->getPublicUrlofFile($path);
                                        $_nhcss[] = $h_value;
                                    }
                                    $_header["head_css"] = $_nhcss;
                                }
                            }
                            if (isset($_header["head_font"])) {
                                if (sizeof($_header["head_font"]) > 0) {
                                    $_nhcss = [];
                                    foreach ($_header["head_font"] as $h_value) {
                                        $path = $h_value["extpath"];
                                        $h_value['public'] = $this->getPublicUrlofFile($path);
                                        $_nhcss[] = $h_value;
                                    }
                                    $_header["head_font"] = $_nhcss;
                                }
                            }
                        }

                        if (isset($theme))
                            $existing = $this->themeconfigRepository->findAll();
                        if ($existing && sizeof($existing) > 0) {
                            $uidd = $existing[0]->getUid();
                            $_uq = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_t3theme_domain_model_themeconfig');
                            $_uq->update('tx_t3theme_domain_model_themeconfig')->where($_uq->expr()->eq('uid', $_uq->createNamedParameter($uidd, Connection::PARAM_INT)));
                            $_uq->set('header', json_encode($_header));
                            $_uq->set('footer', json_encode($_footer));
                            $_uq->set('cssjs', json_encode($_cssjs));
                            $_uq->set('menu', json_encode($_menu));
                            $_uq->set('langm', json_encode($_langm));
                            $_uq->set('general', json_encode($_general));
                            $_uq->executeStatement();
                        } else {
                            $_theme = GeneralUtility::makeInstance(\Crayon\T3theme\Domain\Model\Themeconfig::class);
                            $_theme->setHeader(json_encode($_header));
                            $_theme->setFooter(json_encode($_footer));
                            $_theme->setCssjs(json_encode($_cssjs));
                            $_theme->setMenu(json_encode($_menu));
                            $_theme->setLangm(json_encode($_langm));
                            $_theme->setGeneral(json_encode($_general));
                            $res = $this->themeconfigRepository->add($_theme);
                            /** @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager */
                            $persistenceManager = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager::class);
                            $persistenceManager->persistAll();
                        }
                        return ["status" => 1, "message" => "Imported Successfully!!"];
                    } else {
                        return ["error" => "Invalid json file!!"];
                    }
                } else {
                    return ["error" => "Invalid json file!!"];
                }
            }
        }
    }

    /**
     * @Api\Upload("default")
     * @Api\Route("GET /import/get")
     * @Api\Access("public")
     * @return array
     */
    public function getImportsAction()
    {
        $_extpath = GeneralUtility::getFileAbsFileName("EXT:t3theme/Resources/Public/imports/");
        $file = $_extpath . "imports.json";
        $data = file_get_contents($file);
        $response = null;
        if ($data) {
            $response = json_decode($data);
        }
        return ["data" => $response];
    }

    /**
     * @Api\Upload("default")
     * @Api\Route("POST /import/set")
     * @Api\Access("public")
     * @return array
     */
    public function setImportsAction()
    {

        $_data = $this->request->getBody();
        $_extpath = GeneralUtility::getFileAbsFileName("EXT:t3theme/Resources/Public/imports/");
        $file = $_extpath . "imports.json";
        file_put_contents($file, json_encode($_data));
        $data = file_get_contents($file);
        $response = null;
        if ($data) {
            $response = json_decode($data);
        }
        return ["data" => $response];
    }


    protected function getPageUrl($path)
    {
        $path = rtrim($path, '/');
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->select("*");
        $queryBuilder->from('pages');
        $_where[] = $queryBuilder->expr()->like('slug', $queryBuilder->createNamedParameter('%' . $queryBuilder->escapeLikeWildcards($path) . '%'));
        $queryBuilder->where(...$_where);
        $result = $queryBuilder->executeQuery();
        $pages = $result->fetchAllAssociative();
        if ($pages && sizeof($pages) > 0) {
            $_purl = "t3://page?uid=" . $pages[0]['uid'];
            return $_purl;
        } else {
            return '/';
        }
    }

    protected function createReferenceImage($flpath, $uid, $pid, $tablename, $fldd, $extra = null)
    {
        $resourceFactory = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\ResourceFactory::class);
        try {
            $file = $resourceFactory->getFileObjectFromCombinedIdentifier($flpath);

            $fdata = [
                'uid_local' => $file->getUid(),
                'tablenames' => $tablename,
                'uid_foreign' => $uid,
                'fieldname' => $fldd,
                'pid' => $pid,
            ];
            if ($extra) {
                if (sizeof($extra) > 0) {
                    foreach ($extra as $iv) {
                        $fdata[$iv['field']] = $iv['value'];
                    }
                }
            }
            \nn\t3::Db()->save('sys_file_reference', $fdata);
            return ["status" => 1];
        } catch (\Throwable $th) {
            return ["status" => 0, "msg" => $th->getMessage()];
        }
    }


    protected function addPage($data, $pid)
    {
        $error_messages = [];

        try {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable("pages");
            $queryBuilder->select("*");
            $queryBuilder->from("pages");
            $_where[] = $queryBuilder->expr()->eq("slug", $queryBuilder->createNamedParameter($queryBuilder->escapeLikeWildcards($data['slug'], Connection::PARAM_STR)));
            $queryBuilder->where(...$_where);
            $result = $queryBuilder->executeQuery();
            $_Dara = $result->fetchAllAssociative();


            if (sizeof($_Dara) > 0) {
                $this->removePages($_Dara);
            }
            $childs = null;
            if (isset($data['childs'])) {
                $childs = $data['childs'];
                unset($data['childs']);
            }
            $data['pid'] = $pid;
            $ipage = \nn\t3::Db()->save('pages', $data);
            if ($childs && sizeof($childs) > 0) {
                foreach ($childs as $cpage) {
                    $error_messages[] = $this->addPage($cpage, $ipage['uid']);
                }
            }
        } catch (\Throwable $th) {

            $error_messages[] = $th->getMessage();
        }
        return $error_messages;
    }


    protected function getReferenceUid($table, $wheres)
    {
        try {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
            $queryBuilder->select("*");
            $queryBuilder->from($table);
            if (sizeof($wheres) > 0) {
                foreach ($wheres as $condi) {
                    if ($condi['exp'] == "eq") { 
                        if (gettype($condi['val']) == "string") {
                            $_where[] = $queryBuilder->expr()->eq($condi['key'], $queryBuilder->createNamedParameter($queryBuilder->escapeLikeWildcards($condi['val'],Connection::PARAM_STR)));
                        }else{
                            $_where[] = $queryBuilder->expr()->eq($condi['key'], $queryBuilder->createNamedParameter($queryBuilder->escapeLikeWildcards($condi['val'],Connection::PARAM_INT)));
                        }
                    }
                    if ($condi['exp'] == "like") {
                        $_where[] = $queryBuilder->expr()->like($condi['key'], $queryBuilder->createNamedParameter('%' . $queryBuilder->escapeLikeWildcards($condi['val']) . '%'));
                    }
                }
            }
            $queryBuilder->where(...$_where);
            $result = $queryBuilder->executeQuery();
            $q_data = $result->fetchAllAssociative();
            if ($q_data && sizeof($q_data) > 0) {
                return $q_data;
            } else {
                return null;
            }
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }

    protected function addPageElements($record, $_pidd, $tablename)
    {
        $error_messages = [];

        $_upp = isset($record['uploads']) ? $record['uploads'] : null;
        $tables = isset($record['tables']) ? $record['tables'] : null;
        $links = isset($record['links']) ? $record['links'] : null;
        $childs = isset($record['childs']) ? $record['childs'] : null; 
        $category = isset($record['category']) ? $record['category'] : null;
        $rData = isset($record['rData']) ? $record['rData'] : null;

        if (isset($record['uid'])) {
            unset($record['uid']);
        }
        if (isset($record['uploads'])) {
            unset($record['uploads']);
        }
        if (isset($record['tables'])) {
            unset($record['tables']);
        }
        if (isset($record['links'])) {
            unset($record['links']);
        }
        if (isset($record['childs'])) {
            unset($record['childs']);
        }
        if (isset($record['category'])) {
            unset($record['category']);
        }
        if (isset($record['rData'])) {
            unset($record['rData']);
        }

        if (isset($record['pi_flexform'])) {
            if (isset($record['flexformData'])) {
                $_flexdata = GeneralUtility::xml2array($record['pi_flexform']);
                foreach ($record['flexformData'] as $fk => $fv) {
                    $exploded = explode(',', $fv['val']);
                    if (sizeof($exploded) == 1) {

                        $_fnd = [];
                        $_fnd[] = ["key" => $fv['field'], "val" => $fv['val'], "exp" => "eq"];
                        $___recEds = $this->getReferenceUid($fv['table'], $_fnd);
                        $_fvalls = null;
                        if ($___recEds && sizeof($___recEds) > 0) {
                            foreach ($___recEds as $c_daaa) {
                                $_fvalls = $c_daaa['uid'];
                            }
                        }
                    } else if (sizeof($exploded) > 1) {
                        $pre_fvalls = [];
                        foreach ($exploded as $key => $uiid) {
                            $_fnd_ = [];
                            $_fnd_[] = ["key" => $fv['field'], "val" => $uiid, "exp" => "eq"];
                            $___recEds = $this->getReferenceUid($fv['table'], $_fnd_);
                            $uid_S = null;
                            if ($___recEds && sizeof($___recEds) > 0) {
                                foreach ($___recEds as $c_daaa) {
                                    $uid_S = $c_daaa['uid'];
                                }
                            }
                            if ($uid_S) {
                                $pre_fvalls[] = $uid_S;
                            }
                        }
                        $_fvalls = implode(',', $pre_fvalls);
                    }

                    $keys = explode('][', trim($fk, '[]'));
                    $reference = &$_flexdata;
                    foreach ($keys as $key) {
                        if (!array_key_exists($key, $reference)) {
                            $reference[$key] = [];
                        }
                        $reference = &$reference[$key];
                    }
                    $reference = $_fvalls;
                    unset($reference);
                }
                $flexFormTools = new FlexFormTools();
                $flexFormString = $flexFormTools->flexArray2Xml($_flexdata, true);
                $record['pi_flexform'] = $flexFormString;
            }
        }

        if ($links && sizeof($links) > 0) {
            foreach ($links as $ldata) {
                foreach ($ldata as $lkey => $lvalue) {
                    $path = $this->getPageUrl($lvalue);
                    $record[$lkey] = $path;
                }
            }
        }
        $record['pid'] = $_pidd;
        $record['tstamp'] = time();
        $record['crdate'] = time();  
        try {
            $tt_ = \nn\t3::Db()->save($tablename, $record);
        } catch (\Throwable $th) {
            $error_messages[] = $th->getMessage();
        }

        if($tt_['uid']){ 
            if ($_upp && sizeof($_upp) > 0) {
                foreach ($_upp as $ufl) {
                    $flpath = $ufl['path'];
                    $iref = $this->createReferenceImage($flpath, $tt_['uid'], $tt_['pid'], 'tt_content', $ufl['field']);
                    if ($iref['status'] != 1) {
                        $error_messages[] = $iref['msg'];
                    }
                }
            }
    
            if ($tables && sizeof($tables) > 0) {
                foreach ($tables as $tval) {
                    foreach ($tval as $r_key => $r_val) {
                        $_rtable = $r_key;
                        foreach ($r_val as $rec) {
                            $rec_upload = isset($rec['uploads']) ? $rec['uploads'] : null;
                            $r_links = isset($rec['links']) ? $rec['links'] : null;
    
                            if (isset($rec['uploads'])) {
                                unset($rec['uploads']);
                            }
                            if (isset($rec['uid'])) {
                                unset($rec['uid']);
                            }
                            if (isset($rec['links'])) {
                                unset($rec['links']);
                            }
                            $rec['tt_content'] = $tt_['uid'];
                            $rec['pid'] = $tt_['pid'];
                            $rec['tstamp'] = time();
                            $rec['crdate'] = time();
    
                            if ($r_links && sizeof($r_links) > 0) {
                                foreach ($r_links as $ldata) {
                                    foreach ($ldata as $lkey => $lvalue) {
                                        $path = $this->getPageUrl($lvalue);
                                        $rec[$lkey] = $path;
                                    }
                                }
                            }
    
                            try {
                                $_rrec = \nn\t3::Db()->save($_rtable, $rec);
                            } catch (\Throwable $th) {
                                $error_messages[] = $th->getMessage();
                            }
    
                            $rrecor_id = $_rrec['uid'];
                            if ($rec_upload && sizeof($rec_upload) > 0) {
                                foreach ($rec_upload as $rufl) {
                                    $flpath = $rufl['path'];
                                    $iref = $this->createReferenceImage($flpath, $rrecor_id, $_rrec['pid'], $_rtable, $rufl['field']);
                                    if ($iref['status'] != 1) {
                                        $error_messages[] = $iref['msg'];
                                    }
                                }
                            }
                        }
                    }
                }
            }
    
            if ($childs && sizeof($childs) > 0) {
                foreach ($childs as $ky=>$cdata) {
                    $cdata['tx_container_parent'] = $tt_['uid'];
                    $cdata['sorting'] = $ky;
                    $error_messages[] = $this->addPageElements($cdata, $_pidd, $tablename);
                }
            }
    
            if ($category && sizeof($category) > 0) {
                foreach ($category as $cat) {
                    $find = [];
                    $find[] = ["key" => "slug", "val" => rtrim($cat['slug'], '/'), "exp" => "eq"];
                    $_recEds = $this->getReferenceUid('sys_category', $find);
                    $cid = null;
    
                    if ($_recEds && sizeof($_recEds) > 0) {
                        foreach ($_recEds as $c_daaa) {
                            $cid = $c_daaa['uid'];
                        }
                    }
                    if ($cid) { 
                        $_catRec['uid_local'] = $cid;
                        $_catRec['uid_foreign'] = (int) $tt_['uid'];
                        $_catRec['tablenames'] = 'tt_content';
                        $_catRec['fieldname'] = 'categories';
                        try {
                            $find = [];
                            $find[] = ["key" => "uid_local", "val" => $cid, "exp" => "eq"];
                            $find[] = ["key" => "uid_foreign", "val" => $tt_['uid'], "exp" => "eq"];
                            $find[] = ["key" => "tablenames", "val" => "tt_content", "exp" => "like"];
                            $find[] = ["key" => "fieldname", "val" => "categories", "exp" => "like"];
                            $remove = $this->removeEntries("sys_category_record_mm", $find);
                            $cat_ref = \nn\t3::Db()->save("sys_category_record_mm", $_catRec);
                        } catch (\Throwable $th) { 
                            $error_messages[] = $th->getMessage();
                        }
                    }
                }
            }
 
            
        }
        return $error_messages;
    }

    protected function addtablesDataa($data, $table, $ciden, $pident)
    {
        $error_messages = [];
        if (sizeof($data) > 0) {
            foreach ($data as $rec) {
                $__cid = null;
                if ($ciden) {
                    if (isset($rec[$ciden])) {
                        $__cid = $rec[$ciden];
                    }
                    if (isset($rec[$ciden])) {
                        unset($rec[$ciden]);
                    }
                }

                $rec['tstamp'] = time();
                $rec['crdate'] = time();
                try {
                    $addedentry = \nn\t3::Db()->save($table, $rec);
                } catch (\Throwable $th) {
                    $error_messages[] = $th->getMessage();
                }
                if ($__cid) {
                    try {
                        $error_messages[] = $this->addSubDataofTable($__cid, $pident, $addedentry['uid'], $table, $ciden);
                    } catch (\Throwable $th) {
                        $error_messages[] = $th->getMessage();
                    }
                }
            }
        }
        return $error_messages;
    }
    protected function addSubDataofTable($data, $pident, $pid, $table, $ciden)
    {
        if ($data && sizeof($data) > 0) {
            foreach ($data as $rec) {
                $__cid = isset($rec[$ciden]) ? $rec[$ciden] : null;
                if (isset($rec[$ciden])) {
                    unset($rec[$ciden]);
                }
                $rec['tstamp'] = time();
                $rec['crdate'] = time();
                $rec[$pident] = $pid;
                $addedentry = \nn\t3::Db()->save($table, $rec);
                $subbdentry = $this->addSubDataofTable($__cid, $pident, $addedentry['uid'], $table, $ciden);
            }
        }
    }
    protected function getPublicUrlofFile($path)
    {
        $abspath = GeneralUtility::getFileAbsFileName($path);
        $abspath = str_replace(Environment::getPublicPath(), "", $abspath);
        return $abspath;
    }
    protected function removePageContent($pid)
    {
        try {

            GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tt_content')
                ->delete(
                    'tt_content',
                    ['pid' => $pid]
                );
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }
    protected function removePages($remove)
    {

        try {
            if (sizeof($remove) > 0) {
                foreach ($remove as $page) {
                    $this->removeChildPage($page['uid']);
                    try {
                        GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('pages')
                            ->delete(
                                'pages',
                                ['uid' => $page['uid']]
                            );
                    } catch (\Throwable $th) {
                        return $th->getMessage();
                    }
                }
            }
        } catch (\Throwable $th) {
            $error_messages[] = $th->getMessage();
        }
    }
    protected function removeChildPage($pid)
    {
        try {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
            $queryBuilder->select("*");
            $queryBuilder->from('pages');
            $_where[] = $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pid));
            $queryBuilder->where(...$_where);
            $result = $queryBuilder->executeQuery();
            $Edata = $result->fetchAllAssociative();
            if (sizeof($Edata) > 0) {
                $this->removePages($Edata);
            }
        } catch (\Throwable $th) {
            $error_messages[] = $th->getMessage();
        }
    }
    protected function removeEntries($table, $wheres)
    {
        try {

            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
            $queryBuilder->delete($table);
            if (sizeof($wheres) > 0) {
                foreach ($wheres as $condi) {
                    if ($condi['exp'] == "eq") {
                        $_where[] = $queryBuilder->expr()->eq($condi['key'], $queryBuilder->createNamedParameter($queryBuilder->escapeLikeWildcards($condi['val'])));
                    }
                    if ($condi['exp'] == "like") {
                        $_where[] = $queryBuilder->expr()->like($condi['key'], $queryBuilder->createNamedParameter('%' . $queryBuilder->escapeLikeWildcards($condi['val']) . '%'));
                    }
                }
            }
            return $queryBuilder->where(...$_where)->executeStatement();
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }
    protected function getErrorLEvel($errors, $_err = [])
    {
        if ($errors) {
            if (gettype($errors) == "string") {
                $_err[] = $errors;
            } else {
                if (sizeof($errors) > 0) {
                    foreach ($errors as $_eval) {
                        $_errrs = $this->getErrorLEvel($_eval, $_err);
                        if ($_errrs) {
                            if (sizeof($_errrs) > 0) {
                                foreach ($_errrs as $_value) {
                                    $_err[] = $_value;
                                }
                            }
                        }
                    }
                }
            }
            if (sizeof($_err) > 0) {
                return $_err;
            } else {
                return null;
            }
        } else {
            return null;
        }
    }

    protected function getPageUidBySlug($_slug)
    {
        try {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable("pages");
            $queryBuilder->select("slug", "uid");
            $queryBuilder->from("pages");
            $_where[] = $queryBuilder->expr()->eq("slug", $queryBuilder->createNamedParameter($queryBuilder->escapeLikeWildcards($_slug, Connection::PARAM_STR)));
            $queryBuilder->where(...$_where);
            $result = $queryBuilder->executeQuery();
            $q_data = $result->fetchAllAssociative();
            if ($q_data && sizeof($q_data) > 0) {
                return $q_data[0]['uid'];
            } else {
                return null;
            }
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }

    protected function getContentUidByrecords($ctype,$crdate,$pid){
        try {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable("tt_content");
            $queryBuilder->select("uid");
            $queryBuilder->from("tt_content");
            $_where[] = $queryBuilder->expr()->eq("CType", $queryBuilder->createNamedParameter($queryBuilder->escapeLikeWildcards($ctype, Connection::PARAM_STR)));
            $_where[] = $queryBuilder->expr()->eq("crdate", $queryBuilder->createNamedParameter($queryBuilder->escapeLikeWildcards($crdate, Connection::PARAM_STR)));
            $_where[] = $queryBuilder->expr()->eq("pid", $queryBuilder->createNamedParameter($queryBuilder->escapeLikeWildcards($pid, Connection::PARAM_STR)));
            $queryBuilder->where(...$_where);
            $result = $queryBuilder->executeQuery();
            $q_data = $result->fetchAllAssociative();
            if ($q_data && sizeof($q_data) > 0) {
                return $q_data[0]['uid'];
            } else {
                return null;
            }
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }
}
