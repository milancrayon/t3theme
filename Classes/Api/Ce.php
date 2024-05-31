<?php

namespace  Crayon\T3theme\Api;

use Nng\Nnrestapi\Annotations as Api;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Crayon\T3theme\Domain\Repository\ElementsRepository;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Resource\FileRepository;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Resource\FileCollectionRepository;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Country\CountryProvider;
use Symfony\Component\Mime\Address;
use TYPO3\CMS\Core\Mail\FluidEmail;
use TYPO3\CMS\Core\Mail\MailerInterface;
use TYPO3\CMS\Fluid\View\TemplatePaths;
use TYPO3\CMS\Extbase\Service\ImageService;
use TYPO3\CMS\Core\Imaging\ImageManipulation\CropVariantCollection;
use GeorgRinger\News\Domain\Repository\NewsRepository;
use GeorgRinger\News\Pagination\QueryResultPaginator;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use GeorgRinger\News\Domain\Model\Dto\NewsDemand;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;
use GeorgRinger\News\Domain\Repository\CategoryRepository;
use GeorgRinger\News\Domain\Repository\TagRepository;
use GeorgRinger\News\Domain\Model\Dto\Search; 

/**
 * @Api\Endpoint()
 */
class Ce extends \Nng\Nnrestapi\Api\AbstractApi
{
    /**
     * @var ElementsRepository
     */
    private $elementsRepository = null;

    /** @var array */
    protected $ignoredSettingsForOverride = ['demandclass', 'orderbyallowed', 'selectedList'];

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
        $this->countryProvider = \nn\t3::injectClass(CountryProvider::class);
        $this->imageService = \nn\t3::injectClass(ImageService::class);
        $this->newsRepository = \nn\t3::injectClass(NewsRepository::class);
        $this->categoryRepository = \nn\t3::injectClass(CategoryRepository::class);
        $this->tagRepository = \nn\t3::injectClass(TagRepository::class);
        \nn\t3::Db()->ignoreEnableFields($this->elementsRepository);
    }

    /**
     * @Api\Route("GET /ce/version")
     * @Api\Access("public")
     * @return array
     */
    public function typoVersionAction()
    {
        $typoversion =  GeneralUtility::makeInstance(\TYPO3\CMS\Core\Information\Typo3Version::class);
        return ['status' => 1, 'version' => $typoversion->getVersion()];
    }
    /**
     * @Api\Route("GET /ce/countries")
     * @Api\Access("public")
     * @return array
     */
    public function countriesAction()
    {
        $allCountries = $this->countryProvider->getAll();
        return ['status' => 1, 'countries' => $allCountries];
    }

    /**
     * @Api\Route("GET /ce/youtube/{slug}")
     * @Api\Access("public")
     * @param string $slug
     * @return array
     */
    public function getYoutubeAction($slug = null)
    {
        $_path = 'fileadmin/user_upload/' . $slug;
        if (file_exists($_path)) {
            $__datafile[$slug] = file_get_contents($_path);
            return ['data' => file_get_contents($_path)];
        } else {
            return ['data' => null];
        }
    }

    /**
     * @Api\Route("GET /ce/langs")
     * @Api\Access("public")
     * @return array
     */
    public function langsAction()
    {
        $site = $this->request->getMvcRequest()->getAttribute('site');
        $langues = $site->getLanguages($GLOBALS['BE_USER'], false, 0);
        // touch(time().".txt");


        return ['status' => 1, "languages" => $langues];
    }

    /**
     * @Api\Route("POST /ce/elementdb")
     * @Api\Access("public")
     * @return array
     */
    public function elemenAction()
    {
        $element = $this->request->getBody();
        if ($element['operations']) {
            if (isset($element['uid'])) {
                $dbUpdates = $this->elementsRepository->elementUpdate($element['operations']);
            } else if (isset($element['rmvd'])) {
                $dbUpdates = $this->elementsRepository->elementRemove($element['operations']);
            } else {
                $dbUpdates = $this->elementsRepository->elementOperations($element['operations']);
            }
            if ($dbUpdates) {
                return ['status' => 1, 'msg' => "Element Success!!"];
            } else {
                return ['status' => 0, 'msg' => "database regarding changes not perform due to some error!, please contact support."];
            }
        }
    }
    /**
     * @Api\Route("POST /ce/filedata")
     * @Api\Access("public")
     * 
     * @param string $slug
     * @return array
     */
    public function getFileData()
    {
        $extpath =  GeneralUtility::makeInstance(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::class)::extPath('t3theme');
        $data = $this->request->getBody();
        if ($data['content']) {
            if (sizeof($data['content']) > 0) {
                $__datafile = [];
                $fileswritten = 0;
                foreach ($data['content'] as $key => $value) {
                    $_path = $extpath . $value['path'];
                    if (file_exists($_path)) {
                        $__datafile[$value['path']] = file_get_contents($_path);
                    } else {
                        $__datafile[$value['path']] = "";
                    }
                    $fileswritten = $fileswritten + 1;
                }
                if ($fileswritten == sizeof($data['content'])) {
                    return ['status' => 1, 'data' => $__datafile];
                }
            } else {
                return ['status' => 0, 'msg' => "Something went wrong !"];
            }
        } else {
            return ['status' => 0, 'msg' => "Something went wrong !"];
        }
    }
    /**
     * @Api\Route("POST /ce/rmvfile")
     * @Api\Access("public")
     * 
     * @param string $slug
     * @return array
     */
    public function unFile()
    {
        $extpath =  GeneralUtility::makeInstance(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::class)::extPath('t3theme');
        $data = $this->request->getBody();
        if ($data['content']) {
            if (sizeof($data['content']) > 0) {
                $fileswritten = 0;
                foreach ($data['content'] as $key => $value) {
                    $_path = $extpath . $value['path'];
                    if (file_exists($_path)) {
                        unlink($_path);
                    }
                    $fileswritten = $fileswritten + 1;
                }
                if ($fileswritten == sizeof($data['content'])) {
                    return ['status' => 1];
                }
            } else {
                return ['status' => 0, 'msg' => "Something went wrong !"];
            }
        } else {
            return ['status' => 0, 'msg' => "Something went wrong !"];
        }
    }

    /**
     * @Api\Route("POST /ce/fileupdate")
     * @Api\Access("public")
     * @return array
     */
    public function fileUpdation()
    {
        $data = $this->request->getBody();
        if ($data['content']) {
            if (sizeof($data['content']) > 0) {
                $fileswritten = 0;
                $extpath = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::class)::extPath('t3theme');
                foreach ($data['content'] as $key => $value) {
                    if ($ttcont_dat = fopen($extpath . $value['path'], "w")) {
                        if (fwrite($ttcont_dat, $value['content'])) {
                            fclose($ttcont_dat);
                        }
                        $fileswritten = $fileswritten + 1;
                    }
                }
                if ($fileswritten == sizeof($data['content'])) {
                    $cacheManager =  GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class);
                    $cacheManager->flushCaches();
                    return ['status' => 1, 'msg' => "Files Updated Successfully !"];
                }
            } else {
                return ['status' => 0, 'msg' => "Something went wrong !"];
            }
        } else {
            return ['status' => 0, 'msg' => "Something went wrong !"];
        }
    }

    /**
     * @Api\Route("POST /ce/results")
     * @Api\Access("public")
     * 
     * @return array
     */
    public function getResults()
    {
        $data = $this->request->getBody();
        if (isset($data['table'])) {
            try {
                $table = $data['table'];
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
                $queryBuilder->select("*");
                $queryBuilder->from($table);
                if (isset($data['orderby'])) {
                    if (isset($data['orderby']['by']) && isset($data['orderby']['dirc'])) {
                        $queryBuilder->orderBy($data['orderby']['by'], $data['orderby']['dirc']);
                    }
                }
                if (isset($data['where'])) {
                    $_where = [];
                    $_orWhere = [];
                    if (isset($data['where']['and'])) {
                        if (sizeof($data['where']['and']) > 0) {
                            foreach ($data['where']['and'] as $key => $value) {
                                if ($value['exp'] == "eq") {
                                    $_where[] = $queryBuilder->expr()->eq($value['field'], $queryBuilder->createNamedParameter($value['value']));
                                }
                                if ($value['exp'] == "neq") {
                                    $_where[] = $queryBuilder->expr()->neq($value['field'], $queryBuilder->createNamedParameter($value['value']));
                                }
                                if ($value['exp'] == "like") {
                                    if (sizeof($value['fields'])) {
                                        foreach ($value['fields'] as $ssey => $value__) {
                                            $_where[] = $queryBuilder->expr()->like($value__, $queryBuilder->createNamedParameter('%' . $queryBuilder->escapeLikeWildcards($value['value']) . '%'));
                                        }
                                    }
                                }
                                if ($value['exp'] == "notlike") {
                                    if (sizeof($value['fields'])) {
                                        foreach ($value['fields'] as $ssey => $value__) {
                                            $_where[] = $queryBuilder->expr()->notLike($value__, $queryBuilder->createNamedParameter('%' . $queryBuilder->escapeLikeWildcards($value['value']) . '%'));
                                        }
                                    }
                                }
                            }
                            $queryBuilder->where(...$_where);
                        }
                    }
                    if (isset($data['where']['or'])) {
                        if (sizeof($data['where']['or']) > 0) {
                            foreach ($data['where']['or'] as $key => $value) {
                                if ($value['exp'] == "eq") {
                                    $_orWhere[] = $queryBuilder->expr()->eq($value['field'], $queryBuilder->createNamedParameter($value['value']));
                                }
                                if ($value['exp'] == "neq") {
                                    $_orWhere[] = $queryBuilder->expr()->neq($value['field'], $queryBuilder->createNamedParameter($value['value']));
                                }
                                if ($value['exp'] == "like") {
                                    if (sizeof($value['fields'])) {
                                        foreach ($value['fields'] as $ssey => $value__) {
                                            $_orWhere[] = $queryBuilder->expr()->like($value__, $queryBuilder->createNamedParameter('%' . $queryBuilder->escapeLikeWildcards($value['value']) . '%'));
                                        }
                                    }
                                }
                                if ($value['exp'] == "notlike") {
                                    if (sizeof($value['fields'])) {
                                        foreach ($value['fields'] as $ssey => $value__) {
                                            $_orWhere[] = $queryBuilder->expr()->notLike($value__, $queryBuilder->createNamedParameter('%' . $queryBuilder->escapeLikeWildcards($value['value']) . '%'));
                                        }
                                    }
                                }
                            }
                            $queryBuilder->orWhere(...$_orWhere);
                        }
                    }
                }
                if (isset($data['limit'])) {
                    $queryBuilder->setMaxResults($data['limit']);
                }
                if (isset($data['offset'])) {
                    $queryBuilder->setFirstResult($data['offset']);
                }
                $result = $queryBuilder->executeQuery();
                $__data = $result->fetchAllAssociative(); 
                return ['data' => $__data];
            } catch (Exception $e) {
                return ['error' => $e];
            }
        }
        return ['error' => 'Invalid Request!!'];
    }
    /**
     * @Api\Route("POST /ce/store")
     * @Api\Access("public")
     * 
     * @return array
     */
    public function storeMe()
    {
        $data = $this->request->getBody();
        if (isset($data['table'])) {
            if ($data['table'] && $data['values'] && sizeof($data['values']) > 0) {
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($data['table']);
                $affectedRows = $queryBuilder
                    ->insert($data['table'])
                    ->values($data['values'])
                    ->execute(); 
                return ['data' => $affectedRows];
            }
        }
        return ['error' => 'Invalid Request!!'];
    }
    /**
     * @Api\Route("POST /ce/updats")
     * @Api\Access("public")
     * 
     * @return array
     */
    public function updatsMe()
    {
        $data = $this->request->getBody();
        if (isset($data['table'])) {
            if (isset($data['table']) && isset($data['value']) && isset($data['key']) && isset($data['updates']) && sizeof($data['updates']) > 0) {
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($data['table']);
                $queryBuilder->update($data['table'])->where($queryBuilder->expr()->eq($data['key'], $queryBuilder->createNamedParameter($data['value'])));
                if (sizeof($data['updates']) > 0) {
                    foreach ($data['updates'] as $key => $value) {
                        $queryBuilder->set($value['key'], $value['val']);
                    }
                }
                $result = $queryBuilder->execute();
                return ['data' => $result];
            } else {
                return ['error' => 'Parameters Missing!!'];
            }
        }
        return ['error' => 'Invalid Request!!'];
    }
    /**
     * @Api\Upload("default")
     * @Api\Route("POST /ce/iupload")
     * @Api\Access("public")
     * @return array
     */
    public function uploadAction()
    {
        $updated_paths = [];
        if (sizeof($this->request->getUploadedFiles()) > 0) {
            $uploadedd = $this->request->getUploadedFiles();
            $ext_pub = 'EXT:t3theme/Resources/Public/';
            $_extpath = GeneralUtility::getFileAbsFileName($ext_pub);
            foreach ($uploadedd as $key => $value) {
                $src = $value;
                $directory = '';
                if ($key == 'favicon' || $key == 'logo') {
                    $directory = 'images/';
                } else {
                    if (strpos($key, '_js_')) {
                        $directory = 'js/';
                    }
                    if (strpos($key, '_css_')) {
                        $directory = 'css/';
                    }
                    if (strpos($key, '_font_')) {
                        $directory = 'fonts/';
                    }
                }
                $dir = $_extpath . $directory;
                GeneralUtility::mkdir_deep($dir);
                $updated_paths[$key]['extpath'] = $ext_pub . $directory . pathinfo($src->getClientFilename(), PATHINFO_BASENAME);
                $updated_paths[$key]['public'] = \TYPO3\CMS\Core\Utility\PathUtility::getAbsoluteWebPath($_extpath) . $directory . pathinfo($src->getClientFilename(), PATHINFO_BASENAME);
                $srcFileName = $dir . '/' . pathinfo($src->getClientFilename(), PATHINFO_BASENAME);
                if (!is_string($src) && is_a($src, \TYPO3\CMS\Core\Http\UploadedFile::class)) {
                    if ($stream = $src->getStream()) {
                        $handle = fopen($srcFileName, 'wb+');
                        if ($handle === false) return false;
                        $stream->rewind();
                        while (!$stream->eof()) {
                            $bytes = $stream->read(4096);
                            fwrite($handle, $bytes);
                        }
                        fclose($handle);
                    }
                }
            }
        }
        return ['uploads' => $updated_paths];
    }

    /**
     * @Api\Upload("default")
     * @Api\Route("POST /ce/iget")
     * @Api\Access("public")
     * @return array
     */
    public function imageAction()
    {
        $_data = $this->request->getBody();
        if (is_array($_data)) {
            $_records = [];
            foreach ($_data as $key => $data) {
                if ($data['eid'] && $data['field'] && $data['table']) {
                    $records = $this->getMediaDetails($data);
                    array_push($_records, ["eid" => $data['eid'], "field" => $data['field'], "table" => $data['table'], "images" => $records]);
                    if (sizeof($_data) == sizeof($_records)) {
                        return ["data" => $_records];
                    }
                } else {
                    return ['error' => 'properties missing!!'];
                }
            }
        } else {
            return ["data" => []];
        }
    }


    /**
     * @Api\Route("GET /ce/formdata/{id}")
     * @Api\Access("public")
     * @param int $id
     * @return array
     */
    public function formDataAction($id)
    {
        $cdata = \nn\t3::Content()->get($id, true);
        if ($cdata['pi_flexform']) {

            $pdorm =  GeneralUtility::xml2array($cdata['pi_flexform']);
            $flpath = $pdorm['data']['sDEF']['lDEF']['settings.persistenceIdentifier']['vDEF'];
            $resourceFactory = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\ResourceFactory::class);
            $file = $resourceFactory->getFileObjectFromCombinedIdentifier($flpath);
            $streamlinedFileName = GeneralUtility::getFileAbsFileName($file->getPublicUrl());
            $data =  file_get_contents($streamlinedFileName);
            $content = Yaml::parse($data);
            return ['status' => 1, 'data' => $content];
        } else {
            return ['status' => 0, 'error' => 'Content not get with this Id!!'];
        }
    }
    /**
     * @Api\Route("POST /ce/formsubmit")
     * @Api\Access("public")
     * @return array
     */
    public function formSubmitAction()
    {
        $_data = $this->request->getBody();

        $subject = (string)$_data['soptions']['subject'];
        $recipients = $this->getRecipients(isset($_data['soptions']['recipients']) ? $_data['soptions']['recipients'] : [], $_data['fdata']);
        $senderAddress = (string)$_data['soptions']['senderAddress'];
        $senderName = (string)$_data['soptions']['senderName'];
        $replyToRecipients = $this->getRecipients(isset($_data['soptions']['replyToRecipients']) ? $_data['soptions']['replyToRecipients'] : [], $_data['fdata']);
        $carbonCopyRecipients = $this->getRecipients(isset($_data['soptions']['carbonCopyRecipients']) ? $_data['soptions']['carbonCopyRecipients'] : [], $_data['fdata']);
        $blindCarbonCopyRecipients = $this->getRecipients(isset($_data['soptions']['blindCarbonCopyRecipients']) ? $_data['soptions']['blindCarbonCopyRecipients'] : [], $_data['fdata']);
        $addHtmlPart = isset($_data['soptions']['addHtmlPart']) ? $_data['soptions']['addHtmlPart'] : true;
        $attachUploads = isset($_data['soptions']['attachUploads']) ? $_data['soptions']['attachUploads'] : false;
        $attachments = isset($_data['soptions']['attachments']) ? $_data['soptions']['attachments'] : null;
        $title = (string)$_data['soptions']['title'];


        if ($subject === '') {
            return [
                "error" => 'The option "subject" must be set for the EmailFinisher.'
            ];
        }
        if (empty($recipients)) {
            return [
                "error" => 'The option "recipients" must be set for the EmailFinisher.'
            ];
        }
        if (empty($senderAddress)) {
            return [
                "error" => 'The option "senderAddress" must be set for the EmailFinisher.'
            ];
        }

        $templateConfiguration = $GLOBALS['TYPO3_CONF_VARS']['MAIL'];

        $templateConfiguration['templateRootPaths'] = array_replace_recursive(
            $templateConfiguration['templateRootPaths'],
            [
                '100' => 'EXT:t3theme/Resources/Private/Templates/Form/'
            ]
        );
        ksort($templateConfiguration['templateRootPaths']);

        if (is_array($this->options['layoutRootPaths'] ?? null)) {
            $templateConfiguration['layoutRootPaths'] = array_replace_recursive(
                $templateConfiguration['layoutRootPaths'],
                $this->options['layoutRootPaths']
            );
            ksort($templateConfiguration['layoutRootPaths']);
        }



        $mail = GeneralUtility::makeInstance(
            FluidEmail::class,
            GeneralUtility::makeInstance(TemplatePaths::class, $templateConfiguration)
        );
        if (($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface) {
            $mail->setRequest($GLOBALS['TYPO3_REQUEST']);
        }


        $mail->from(new Address($senderAddress, $senderName))
            ->to(...$recipients)
            ->subject($subject)
            ->format($addHtmlPart ? FluidEmail::FORMAT_BOTH : FluidEmail::FORMAT_PLAIN)
            ->setTemplate('Default')
            ->assign('title', $title)
            ->assign('markers', $_data['markers']);

        if (!empty($replyToRecipients)) {
            $mail->replyTo(...$replyToRecipients);
        }

        if (!empty($carbonCopyRecipients)) {
            $mail->cc(...$carbonCopyRecipients);
        }

        if (!empty($blindCarbonCopyRecipients)) {
            $mail->bcc(...$blindCarbonCopyRecipients);
        }

        if ($attachUploads) {
            if($attachments){
                foreach($attachments as $filname){ 
                    $file = GeneralUtility::makeInstance(ResourceFactory::class)->getFileObjectFromCombinedIdentifier($filname);
                    $mail->attach($file->getContents(), $file->getName(), $file->getMimeType());
                } 
            }
        }


        GeneralUtility::makeInstance(MailerInterface::class)->send($mail);

        return [
            'status' => 1,
            'msg' => "EMail send successfully!!"
        ];
    }


    /**
     * Get mail recipients
     *
     */
    protected function getRecipients($listOption, $fdata): array
    {
        $recipients = $listOption ?? [];
        if (!is_array($recipients) || $recipients === []) {
            return [];
        }

        $addresses = [];
        foreach ($recipients as $address => $name) {
            $address = str_replace("{", "", $address);
            $address = str_replace("}", "", $address);
            $name = str_replace("{", "", $name);
            $name = str_replace("}", "", $name);
            if (isset($fdata[$address])) {
                $address = $fdata[$address];
            }
            if (isset($fdata[$name])) {
                $name = $fdata[$name];
            }

            if (!GeneralUtility::validEmail($address)) {
                continue;
            }
            $addresses[] = new Address($address, $name);
        }
        return $addresses;
    }

    /**
     * @Api\Route("GET /ce/flexdata/{id}")
     * @Api\Access("public")
     * @param int $id
     * @return array
     */
    public function flexdataAction($id)
    {
        $cdata = \nn\t3::Content()->get($id, true);
        if ($cdata['pi_flexform']) {
            $pdorm =  GeneralUtility::xml2array($cdata['pi_flexform']);
            return ['status' => 1, 'data' => $pdorm];
        } else {
            return ['status' => 0, 'error' => 'Content not get with this Id!!'];
        }
    }


    /**
     * @Api\Localize()
     * @Api\Route("GET /ce/blogs/{id}")
     * @Api\Access("public")
     * @param int $id
     * @return array
     */
    public function blogDataAction($id)
    {
        $cdata = \nn\t3::Content()->get($id, true);
        $_data = $this->request->getArguments();
        $currentpage = isset($_data['page']) ? $_data['page'] : null;

        if ($cdata['pi_flexform']) {
            $pdorm =  GeneralUtility::xml2array($cdata['pi_flexform']);
            $categores = isset($pdorm['data']['sDEF']['lDEF']['settings.categories']['vDEF']) ? $pdorm['data']['sDEF']['lDEF']['settings.categories']['vDEF'] : null;
            $categoryConjunction = isset($pdorm['data']['sDEF']['lDEF']['settings.categoryConjunction']['vDEF']) ? $pdorm['data']['sDEF']['lDEF']['settings.categoryConjunction']['vDEF'] : null;
            $tags = isset($pdorm['data']['additional']['lDEF']['settings.tags']['vDEF']) ? $pdorm['data']['additional']['lDEF']['settings.tags']['vDEF'] : null;
            $topNewsRestriction = isset($pdorm['data']['sDEF']['lDEF']['settings.topNewsRestriction']['vDEF']) ? $pdorm['data']['sDEF']['lDEF']['settings.topNewsRestriction']['vDEF'] : null;
            $timeRestriction = isset($pdorm['data']['sDEF']['lDEF']['settings.timeRestriction']['vDEF']) ? $pdorm['data']['sDEF']['lDEF']['settings.timeRestriction']['vDEF'] : null;
            $timeRestrictionHigh = isset($pdorm['data']['sDEF']['lDEF']['settings.timeRestrictionHigh']['vDEF']) ? $pdorm['data']['sDEF']['lDEF']['settings.timeRestrictionHigh']['vDEF'] : null;
            $archiveRestriction = isset($pdorm['data']['sDEF']['lDEF']['settings.archiveRestriction']['vDEF']) ? $pdorm['data']['sDEF']['lDEF']['settings.archiveRestriction']['vDEF'] : null;
            $excludeAlreadyDisplayedNews = isset($pdorm['data']['additional']['lDEF']['settings.excludeAlreadyDisplayedNews']['vDEF']) ? $pdorm['data']['additional']['lDEF']['settings.excludeAlreadyDisplayedNews']['vDEF'] : null;
            $orderBy = isset($pdorm['data']['sDEF']['lDEF']['settings.orderBy']['vDEF']) ? $pdorm['data']['sDEF']['lDEF']['settings.orderBy']['vDEF'] : null;
            $orderDirection = isset($pdorm['data']['sDEF']['lDEF']['settings.orderDirection']['vDEF']) ? $pdorm['data']['sDEF']['lDEF']['settings.orderDirection']['vDEF'] : null;
            $topNewsFirst = isset($pdorm['data']['additional']['lDEF']['settings.topNewsFirst']['vDEF']) ? $pdorm['data']['additional']['lDEF']['settings.topNewsFirst']['vDEF'] : null;
            $limit = isset($pdorm['data']['additional']['lDEF']['settings.limit']['vDEF']) ? $pdorm['data']['additional']['lDEF']['settings.limit']['vDEF'] : null;
            $offset = isset($pdorm['data']['additional']['lDEF']['settings.offset']['vDEF']) ? $pdorm['data']['additional']['lDEF']['settings.offset']['vDEF'] : null;
            $dateField = isset($pdorm['data']['sDEF']['lDEF']['settings.dateField']['vDEF']) ? $pdorm['data']['sDEF']['lDEF']['settings.dateField']['vDEF'] : null;
            $startingpoint = isset($pdorm['data']['sDEF']['lDEF']['settings.startingpoint']['vDEF']) ? $pdorm['data']['sDEF']['lDEF']['settings.startingpoint']['vDEF'] : null;
            $recursive = isset($pdorm['data']['sDEF']['lDEF']['settings.recursive']['vDEF']) ? $pdorm['data']['sDEF']['lDEF']['settings.recursive']['vDEF'] : null;
            $hidePagination = isset($pdorm['data']['additional']['lDEF']['settings.hidePagination']['vDEF']) ? $pdorm['data']['additional']['lDEF']['settings.hidePagination']['vDEF'] : null;
            $perPAge = isset($pdorm['data']['additional']['lDEF']['settings.list.paginate.itemsPerPage']['vDEF']) ? $pdorm['data']['additional']['lDEF']['settings.list.paginate.itemsPerPage']['vDEF'] : null;
            $disableOverrideDemand = isset($pdorm['data']['additional']['lDEF']['settings.disableOverrideDemand']['vDEF']) ? $pdorm['data']['additional']['lDEF']['settings.disableOverrideDemand']['vDEF'] : null;

            $demand = GeneralUtility::makeInstance(\GeorgRinger\News\Domain\Model\Dto\NewsDemand::class, $pdorm);
            $demand->setCategories(GeneralUtility::trimExplode(',', $categores ?? '', true));
            $demand->setCategoryConjunction((string)($categoryConjunction ?? ''));
            $demand->setTags((string)($tags ?? ''));
            $demand->setTopNewsRestriction((int)($topNewsRestriction  ?? 0));
            $demand->setTimeRestriction($timeRestriction ?? '');
            $demand->setTimeRestrictionHigh($timeRestrictionHigh ?? '');
            $demand->setArchiveRestriction((string)($archiveRestriction ?? ''));
            $demand->setExcludeAlreadyDisplayedNews((bool)($excludeAlreadyDisplayedNews ?? false));
            $demand->setHideIdList((string)(''));
            if ($orderBy ?? '') {
                $demand->setOrder($orderBy . ' ' . $orderDirection);
            }
            $demand->setOrderByAllowed((string)("sorting,author,uid,title,teaser,author,tstamp,crdate,datetime,categories.title"));
            $demand->setTopNewsFirst((bool)($topNewsFirst ?? false));
            $demand->setLimit((int)($limit ?? 0));
            $demand->setOffset((int)($offset ?? 0));
            $demand->setSearchFields((string)(''));
            $demand->setDateField((string)($dateField ?? ''));
            $demand->setMonth((int)(0));
            $demand->setYear((int)(0));
            $Page = GeneralUtility::makeInstance(\GeorgRinger\News\Utility\Page::class);
            $demand->setStoragePage($Page::extendPidListByChildren(
                (string)($startingpoint ?? ''),
                (int)($recursive ?? 0)
            ));

            if ($hooks = $GLOBALS['TYPO3_CONF_VARS']['EXT']['news']['Controller/NewsController.php']['createDemandObjectFromSettings'] ?? []) {
                $params = [
                    'demand' => $demand,
                    'settings' => $settings,
                    'class' => $class,
                ];
                foreach ($hooks as $reference) {
                    GeneralUtility::callUserFunction($reference, $params, $this);
                }
            }
            $demand->setActionAndClass('list', \GeorgRinger\News\Controller\NewsController::class);
            $cattree = null;
            $statistics = null;
            $_currentPage = null;
            $pagination = null;
            $taggs = null;
            if ($cdata['CType'] == "news_newsselectedlist") {
                $selectedList = $pdorm['data']['sDEF']['lDEF']['settings.selectedList']['vDEF'];
                if (empty($orderBy ?? '')) {
                    $idList = GeneralUtility::trimExplode(',', $selectedList, true);
                    foreach ($idList as $id) {
                        $news = $this->newsRepository->findByIdentifier($id);
                        if ($news) {
                            $newsRecords[] = $news;
                        }
                    }
                } else {
                    $demand->setIdList($selectedList);
                    $newsRecords = $this->newsRepository->findDemanded($demand);
                }
            } elseif ($cdata['CType'] == 'news_newsdatemenu') {

                $demand->setLimit(0);
                $demand->setOffset(0);
                if ($orderDirection == '') {
                    $orderDirection = 'desc';
                }
                $demand->setOrder($dateField . ' ' . $orderDirection);
                $newsRecords = $this->newsRepository->findDemanded($demand);
                $demand->setOrder($orderDirection);
                $statistics = $this->newsRepository->countByDate($demand);
            } elseif ($cdata['CType'] == 'news_categorylist') {
                $idList = explode(',', $categores);
                if ($startingpoint == "") {
                    $startingpoint = null;
                }
                $cattree = $this->categoryRepository->findTree($idList, $startingpoint);
                $newsRecords = [];
            } elseif ($cdata['CType'] == 'news_taglist') {
                if ($orderBy === 'datetime') {
                    unset($orderBy);
                }
                $taggs = $this->tagRepository->findDemanded($demand);
                $newsRecords = [];
            } else {
                $year = isset($_data['year']) ? $_data['year'] : null;
                $month = isset($_data['month']) ? $_data['month'] : null;
                $tag = isset($_data['tag']) ? $_data['tag'] : null;
                $_cat_filter = isset($_data['category']) ? $_data['category'] : null;

                $overwriteDemand = [];
                if ($year) {
                    $overwriteDemand['year'] = $year;
                }
                if ($month) {
                    $overwriteDemand['month'] = $month;
                }
                if ($_cat_filter) {
                    $overwriteDemand['categories'] = $_cat_filter;
                }
                if ($tag) {
                    $overwriteDemand['tags'] = $tag;
                }
                if ((int)($disableOverrideDemand ?? 1) !== 1 && $overwriteDemand !== null) {
                    $demand = $this->overwriteDemandObject($demand, $overwriteDemand);
                }
                if ($cdata['CType'] == 'news_newssearchresult') {
                    $subject = isset($_data['subject']) ? $_data['subject'] : null;
                    $minimumDate = isset($_data['minimumDate']) ? $_data['minimumDate'] : null;
                    $maximumDate = isset($_data['maximumDate']) ? $_data['maximumDate'] : null;
                    $search = GeneralUtility::makeInstance(Search::class);


                    if (($subject && $subject != "") || ($minimumDate && $minimumDate != "") || ($maximumDate && $maximumDate != "")) {
                        $search->setFields("teaser,title,bodytext");
                        $search->setDateField("datetime");
                        $search->setSplitSubjectWords((bool)false);
                        if ($subject)
                            $search->setSubject($subject);
                        if ($minimumDate) {
                            $search->setMinimumDate($minimumDate);
                        }
                        if ($maximumDate) {
                            $search->setMaximumDate($maximumDate);
                        }
                        $demand->setSearch($search);
                        $newsRecords = $this->newsRepository->findDemanded($demand);
                    } else {
                        $newsRecords = [];
                    }
                } else {
                    $newsRecords = $this->newsRepository->findDemanded($demand);
                }
                $statistics = null;
            }


            if ($cdata['CType'] != "news_newsselectedlist" && $cdata['CType'] != "news_newsdatemenu" && $cdata['CType'] != "news_categorylist" && $cdata['CType'] != "news_taglist") {
                $paginationConfiguration['class'] = "GeorgRinger\NumberedPagination\NumberedPagination";
                $paginationConfiguration['itemsPerPage'] = $perPAge;
                $paginationConfiguration['insertAbove'] = 1;
                $paginationConfiguration['insertBelow'] = 1;
                $paginationConfiguration['maximumNumberOfLinks'] = 3;
                $itemsPerPage = (int)(($paginationConfiguration['itemsPerPage'] ?? '') ?: 10);
                $maximumNumberOfLinks = (int)($paginationConfiguration['maximumNumberOfLinks'] ?? 0);
                $_currentPage = max(1, $currentpage ? (int)$currentpage : 1);
                if (sizeof($newsRecords) > 0) {
                    $paginator = GeneralUtility::makeInstance(QueryResultPaginator::class, $newsRecords, $_currentPage, $itemsPerPage, (int)($limit ?? 0), (int)($offset ?? 0));
                    $paginationClass = $paginationConfiguration['class'] ?? SimplePagination::class;
                    $pagination = $this->getPagination($paginationClass, $maximumNumberOfLinks, $paginator);
                } else {
                    $paginator = null;
                }
            } else {
                $paginator = null;
            }

            $records = [];
            if ($hidePagination) {
                foreach ($newsRecords as $news) {
                    $temp = $this->getNewsJson($news);
                    $records[] = $temp;
                }
            } else {
                if ($paginator) {
                    if (sizeof($paginator->getPaginatedItems()) > 0) {
                        foreach ($paginator->getPaginatedItems() as $news) {
                            $temp = $this->getNewsJson($news);
                            $records[] = $temp;
                        }
                    }
                } else {
                    foreach ($newsRecords as $news) {
                        $temp = $this->getNewsJson($news);
                        $records[] = $temp;
                    }
                }
            }
            $pagination = [
                'currentPage' => $_currentPage,
                'pagination' => $pagination,
            ];
            return ['status' => 1, 'data' => [
                // 'categories' => $categories, 'tags' => $_tags, 
                'items' => $records,
                'pagination' => $pagination,
                'hidePagination' => $hidePagination,
                'statistics' => $statistics,
                'tagList' => $taggs,
                'categoriesTree' => $cattree
            ]];
        } else {
            return ['status' => 0, 'error' => 'Content not get with this Id!!'];
        }
    }

    /**
     * @Api\Route("GET /ce/blogdata/{id}")
     * @Api\Access("public")
     * @param int $id
     * @return array
     */
    public function blogDetailsAction($id)
    {
        $news = $this->newsRepository->findByUid($id, false);
        if ($news) {
            $data = $this->getNewsJson($news);
            $data['elements'] = $news->getContentElements();
            $_ediadata['eid'] = $id;
            $_ediadata['field'] = 'fal_media';
            $_ediadata['table'] = 'tx_news_domain_model_news';
            $_edmedias = $this->getMediaDetails($_ediadata);
            $_falmedias = [];
            foreach ($_edmedias as $md) {
                foreach ($news->getMediaNonPreviews() as $_md) {
                    if ($_md->getUid() == $md['uid'] && $md['uid'] != $news->getFirstPreview()->getUid()) {
                        $_falmedias[] = $md;
                    }
                }
            }
            $data['media'] = $_falmedias;
            $relmediadata['eid'] = $id;
            $relmediadata['field'] = 'fal_related_files';
            $relmediadata['table'] = 'tx_news_domain_model_news';
            $relatedmedias = $this->getMediaDetails($relmediadata);
            $r_falmedias = [];
            foreach ($relatedmedias as $md) {
                foreach ($news->getRelatedFiles() as $_md) {
                    if ($_md->getUid() == $md['uid']) {
                        $r_falmedias[] = $md;
                    }
                }
            }
            $data['related_files'] = $r_falmedias;
            $data['related'] = [];
            if (sizeof($news->getRelated()) > 0) {
                foreach ($news->getRelated() as $nw) {
                    $temp = $this->getNewsJson($nw);
                    $data['related'][] = $temp;
                }
            }
            return ['status' => 1, 'data' => $data];
        } else {
            return ['status' => 1, 'error' => 'No News Found!!'];
        }
    }

    /**
     * @param $paginationClass
     * @param int $maximumNumberOfLinks
     * @param $paginator
     * @return \GeorgRinger\News\Controller\NewsController.getPagination.0|NumberedPagination|mixed|\Psr\Log\LoggerAwareInterface|string|SimplePagination|\TYPO3\CMS\Core\SingletonInterface
     */
    protected function getPagination($paginationClass, int $maximumNumberOfLinks, $paginator)
    {
        if (class_exists(NumberedPagination::class) && $paginationClass === NumberedPagination::class && $maximumNumberOfLinks) {
            $pagination = GeneralUtility::makeInstance(NumberedPagination::class, $paginator, $maximumNumberOfLinks);
        } elseif (class_exists(SlidingWindowPagination::class) && $paginationClass === SlidingWindowPagination::class && $maximumNumberOfLinks) {
            $pagination = GeneralUtility::makeInstance(SlidingWindowPagination::class, $paginator, $maximumNumberOfLinks);
        } elseif (class_exists($paginationClass)) {
            $pagination = GeneralUtility::makeInstance($paginationClass, $paginator);
        } else {
            $pagination = GeneralUtility::makeInstance(SimplePagination::class, $paginator);
        }
        return $pagination;
    }

    protected function getNewsJson($news)
    {
        $temp['title'] = $news->getTitle();
        $temp['alternative'] = $news->getAlternativeTitle();
        $temp['teaser'] = $news->getTeaser();
        $temp['bodytext'] = $news->getBodytext();
        $temp['datetime'] = $news->getDatetime();
        $temp['year_of_datetime'] = $news->getYearOfDatetime();
        $temp['month_of_datetime'] = $news->getMonthOfDatetime();
        $temp['day_of_datetime'] = $news->getDayOfDatetime();
        $temp['sys_language_uid'] = $news->getSysLanguageUid();
        $temp['archive'] = $news->getArchive();
        $temp['year_of_archive'] = $news->getYearOfArchive();
        $temp['month_of_archive'] = $news->getMonthOfArchive();
        $temp['day_of_archive'] = $news->getDayOfArchive();
        $temp['author'] = $news->getAuthor();
        $temp['author_email'] = $news->getAuthorEmail();
        $temp['type'] = $news->getType();
        $temp['tags'] = $news->getTags();
        $temp['path_segment'] = $news->getPathSegment();
        $temp['crdate'] = $news->getCrdate();
        $temp['uid'] = $news->getUid();
        $temp['tstamp'] = $news->getTstamp();
        $temp['notes'] = $news->getNotes();
        $temp['keywords'] = $news->getKeywords();
        $temp['description'] = $news->getDescription();
        $temp['categories'] = $news->getCategories();
        $temp['first_category'] = $news->getFirstCategory();
        $mediadata['eid'] = $news->getUid();
        $mediadata['field'] = 'fal_media';
        $mediadata['table'] = 'tx_news_domain_model_news';
        $medias = $this->getMediaDetails($mediadata);
        $_falmedias = [];
        foreach ($medias as $md) {
            if ($news->getFirstPreview()) {
                if ($news->getFirstPreview()->getUid() == $md['uid']) {
                    $_falmedias[] = $md;
                }
            }
        }
        $temp['mainimage'] = $_falmedias;

        return $temp;
    }

    protected function getMediaDetails($data)
    {
        if ($data['field'] == "files") {
            $_files = GeneralUtility::makeInstance(FileCollectionRepository::class)->findByUid($data['eid']);
            $_files->loadContents();
            $files = $_files->getItems();
        } else {
            $files = GeneralUtility::makeInstance(FileRepository::class)->findByRelation($data['table'], $data['field'], $data['eid']);
        }
        $records = [];
        if (sizeof($files) > 0) {
            foreach ($files as $key => $value) {
                $file = GeneralUtility::makeInstance(ResourceFactory::class)->getFileObjectFromCombinedIdentifier($value->getCombinedIdentifier());
                $cropString = $value->getProperty('crop');
                if (is_array($cropString)) {
                    $cropString = json_encode($cropString);
                }
                $cropVariantCollection = CropVariantCollection::create((string)$cropString);
                $cropVariant = 'default';
                $cropArea = $cropVariantCollection->getCropArea($cropVariant);
                $processingInstructions = [
                    'crop' => $cropArea->isEmpty() ? null : $cropArea->makeAbsoluteBasedOnFile($value),
                ];
                $processedImage = $this->imageService->applyProcessingInstructions($file, $processingInstructions);
                $imageUri = $this->imageService->getImageUri($processedImage, false);
                $_file['title'] = $value->getTitle();
                $_file['name'] = $value->getName();
                $_file['description'] = $value->getDescription();
                $_file['alternative'] = $value->getAlternative();
                $_file['link'] = $value->getLink();
                $_file['identifier'] = $value->getIdentifier();

                $_file['uid'] = $value->getUid();
                $_file['width'] = $processedImage->getProperty('width');
                $_file['height'] = $processedImage->getProperty('height');
                $_file['tstamp'] = $value->getProperty('tstamp');
                $_file['extension'] = $value->getExtension();
                $_file['url'] = $imageUri;
                $_file['missing'] = $value->isMissing();
                $_file['size'] = $value->getSize();
                $_file['mimetype'] = $value->getMimeType();
                $_file['creation'] = $value->getCreationTime();
                $_file['modification'] = $value->getModificationTime();
                $_file['type'] = $value->getType();
                $records[] = $_file;
            }
        }
        return $records;
    }

    /**
     * Overwrites a given demand object by an propertyName =>  $propertyValue array
     *
     * @param \GeorgRinger\News\Domain\Model\Dto\NewsDemand $demand
     * @param array $overwriteDemand
     * @return \GeorgRinger\News\Domain\Model\Dto\NewsDemand
     */
    protected function overwriteDemandObject(NewsDemand $demand, array $overwriteDemand): \GeorgRinger\News\Domain\Model\Dto\NewsDemand
    {
        foreach ($this->ignoredSettingsForOverride as $property) {
            unset($overwriteDemand[$property]);
        }

        foreach ($overwriteDemand as $propertyName => $propertyValue) {
            if (in_array(strtolower($propertyName), $this->ignoredSettingsForOverride, true)) {
                continue;
            }
            if ($propertyValue !== '') {
                if (in_array($propertyName, ['categories'], true)) {
                    if (!is_array($propertyValue)) {
                        $propertyValue = GeneralUtility::trimExplode(',', $propertyValue, true);
                    }
                }
                ObjectAccess::setProperty($demand, $propertyName, $propertyValue);
            }
        }
        return $demand;
    }

    /**
     * @Api\Upload("default")
     * @Api\Route("POST /ce/fupload")
     * @Api\Access("public")
     * @return array
     */
    public function formuploadAction()
    {
        $updated_paths = [];
        if (sizeof($this->request->getUploadedFiles()) > 0) {
            $uploadedd = $this->request->getUploadedFiles();
            $datta = $this->request->getMvcRequest()->getParsedBody(); 
            foreach ($uploadedd as $ukey => $uvalue) {
                foreach ($datta as $dkey => $dvalue) {
                    if ($dkey == $ukey) {  
                        if (!is_string($uvalue) && is_a($uvalue, \TYPO3\CMS\Core\Http\UploadedFile::class)) {
                            $resourceFactory = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\ResourceFactory ::class); 
                            $file = $resourceFactory->getFolderObjectFromCombinedIdentifier ($dvalue);
                            $srcFileName =$file->getPublicUrl() . pathinfo($uvalue->getClientFilename(), PATHINFO_BASENAME);
                            $updated_paths[$ukey][]=$srcFileName;
                            if ($stream = $uvalue->getStream()) {
                                $handle = fopen($srcFileName, 'wb+');
                                if ($handle === false) return false;
                                $stream->rewind();
                                while (!$stream->eof()) {
                                    $bytes = $stream->read(4096);
                                    fwrite($handle, $bytes);
                                }
                                fclose($handle);
                            }
                        }
                    }
                }
            }  
        }
        return ['uploads' => $updated_paths];
    }
}
