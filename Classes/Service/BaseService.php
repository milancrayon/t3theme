<?php

namespace Crayon\T3theme\Service;
 
use Crayon\T3theme\Utilities\Obj;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use Doctrine\DBAL\Exception\TableNotFoundException;
use TYPO3\CMS\Core\Core\Environment;

class BaseService
{
    public function versionAction(): array
    {
        $typoversion = GeneralUtility::makeInstance(Typo3Version::class);
        return ['status' => 1, 'version' => $typoversion->getVersion()];
    }

    /**
     * @return array<string, mixed>
     */
    public function countryAction(): array
    {
        return ['status' => 1, 'countries' => []];
    }

    /**
     * @param int $uid
     * @return array<string, mixed>
     */
    public function formDataAction(int $uid): array
    {
        return ['status' => 1, 'uid' => $uid, 'data' => []];
    }

    /**
     * @param int $uid
     * @return array<string, mixed>
     */
    public function fetchFlexFormAction(int $uid): array
    {
        return ['status' => 1, 'uid' => $uid, 'flexform' => ''];
    }


    /**
     * @param \TYPO3\CMS\Core\Site\Entity\Site $site
     * @return array<string, mixed>
     */
    public function languagesAction(\TYPO3\CMS\Core\Site\Entity\Site $site): array
    {
        $langues = $site->getLanguages();
        $objUtility = GeneralUtility::makeInstance(Obj::class);
        $depth = 5;
        $languesArray = $objUtility->toArray(true, $depth, [], $langues);
        return ['status' => 1, "languages" => $languesArray];
    }

    /**
     * @param array<string, mixed> $element
     * @return array<string, mixed>
     */
    public function elementDatabaseAction(array $element): array
    {
        return ['status' => 1, 'msg' => "Element Success!!"];
    }
    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function fetchFileDataAction(array $data): array
    {
        $extpath = ExtensionManagementUtility::extPath('t3themeextend');
        if (isset($data['content']) && is_array($data['content'])) {
            if (sizeof($data['content']) > 0) {
                $__datafile = [];
                $fileswritten = 0;
                foreach ($data['content'] as $value) {
                    $_path = $extpath . $value['path'];
                    if (file_exists($_path)) {
                        $__datafile[$value['path']] = (string)file_get_contents($_path);
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
        return ['status' => 0, 'msg' => "Something went wrong !"];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function removeFileAction(array $data): array
    {
        $extpath = ExtensionManagementUtility::extPath('t3themeextend');
        if (isset($data['content']) && is_array($data['content'])) {
            if (sizeof($data['content']) > 0) {
                $fileswritten = 0;
                foreach ($data['content'] as $value) {
                    $_path = $extpath . $value['path'];
                    if (str_starts_with($value['path'], 'Configuration/TCA/Overrides/Elements/')) {
                        $value['path'] = str_replace(
                            'Configuration/TCA/Overrides/Elements/',
                            'Configuration/TCA/Overrides/',
                            $value['path']
                        );
                        $_path = $extpath . $value['path'];
                    }
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
        return ['status' => 0, 'msg' => "Something went wrong !"];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function fileUpdationAction(array $data): array
    {
        if (empty($data['content']) || !is_array($data['content'])) {
            return ['status' => 0, 'msg' => 'Something went wrong !'];
        }

        $filesWritten = 0;
        $basePath = ExtensionManagementUtility::extPath('t3themeextend');

        foreach ($data['content'] as $value) {
            $relativePath = (string)$value['path'];
            if ($relativePath === 'ext_tables.sql') {
                $filesWritten++;
                continue;
            }
            if ($relativePath === 'Configuration/TCA/Overrides/tt_content.php') {
                $filesWritten++;
                continue;
            }
            if ($relativePath === 'Resources/Private/Partials/Page/Lang.html') {
                $filesWritten++;
                continue;
            }
            if (str_starts_with($relativePath, 'Configuration/TCA/Overrides/Elements/')) {
                $relativePath = str_replace(
                    'Configuration/TCA/Overrides/Elements/',
                    'Configuration/TCA/Overrides/',
                    $relativePath
                );
            }
            $absoluteFilePath = $basePath . $relativePath;
            GeneralUtility::mkdir_deep(dirname($absoluteFilePath));
            if (file_put_contents($absoluteFilePath, $value['content']) !== false) {
                $filesWritten++;
            }
        }

        if ($filesWritten === count($data['content'])) {
            $cacheManager = GeneralUtility::makeInstance(
                \TYPO3\CMS\Core\Cache\CacheManager::class
            );
            $cacheManager->flushCaches();
            return ['status' => 1, 'msg' => 'Files Updated Successfully !'];
        }

        return ['status' => 0, 'msg' => 'Something went wrong !'];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function resultAction(array $data): array
    {
        if (isset($data['table'])) {
            try {
                $table = (string)$data['table'];
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
                    if (isset($data['where']['and']) && is_array($data['where']['and'])) {
                        if (sizeof($data['where']['and']) > 0) {
                            foreach ($data['where']['and'] as $value) {
                                if ($value['exp'] == "eq") {
                                    $_where[] = $queryBuilder->expr()->eq($value['field'], $queryBuilder->createNamedParameter($value['value']));
                                }
                                if ($value['exp'] == "neq") {
                                    $_where[] = $queryBuilder->expr()->neq($value['field'], $queryBuilder->createNamedParameter($value['value']));
                                }
                                if ($value['exp'] == "like") {
                                    if (isset($value['fields']) && is_array($value['fields'])) {
                                        foreach ($value['fields'] as $value__) {
                                            $_where[] = $queryBuilder->expr()->like($value__, $queryBuilder->createNamedParameter('%' . $queryBuilder->escapeLikeWildcards($value['value']) . '%'));
                                        }
                                    }
                                }
                                if ($value['exp'] == "notlike") {
                                    if (isset($value['fields']) && is_array($value['fields'])) {
                                        foreach ($value['fields'] as $value__) {
                                            $_where[] = $queryBuilder->expr()->notLike($value__, $queryBuilder->createNamedParameter('%' . $queryBuilder->escapeLikeWildcards($value['value']) . '%'));
                                        }
                                    }
                                }
                            }
                            if (!empty($_where)) {
                                $queryBuilder->where(...$_where);
                            }
                        }
                    }
                    if (isset($data['where']['or']) && is_array($data['where']['or'])) {
                        if (sizeof($data['where']['or']) > 0) {
                            foreach ($data['where']['or'] as $value) {
                                if ($value['exp'] == "eq") {
                                    $_orWhere[] = $queryBuilder->expr()->eq($value['field'], $queryBuilder->createNamedParameter($value['value']));
                                }
                                if ($value['exp'] == "neq") {
                                    $_orWhere[] = $queryBuilder->expr()->neq($value['field'], $queryBuilder->createNamedParameter($value['value']));
                                }
                                if ($value['exp'] == "like") {
                                    if (isset($value['fields']) && is_array($value['fields'])) {
                                        foreach ($value['fields'] as $value__) {
                                            $_orWhere[] = $queryBuilder->expr()->like($value__, $queryBuilder->createNamedParameter('%' . $queryBuilder->escapeLikeWildcards($value['value']) . '%'));
                                        }
                                    }
                                }
                                if ($value['exp'] == "notlike") {
                                    if (isset($value['fields']) && is_array($value['fields'])) {
                                        foreach ($value['fields'] as $value__) {
                                            $_orWhere[] = $queryBuilder->expr()->notLike($value__, $queryBuilder->createNamedParameter('%' . $queryBuilder->escapeLikeWildcards($value['value']) . '%'));
                                        }
                                    }
                                }
                            }
                            if (!empty($_orWhere)) {
                                $queryBuilder->orWhere(...$_orWhere);
                            }
                        }
                    }
                }
                if (isset($data['limit'])) {
                    $queryBuilder->setMaxResults((int)$data['limit']);
                }
                if (isset($data['offset'])) {
                    $queryBuilder->setFirstResult((int)$data['offset']);
                }
                $result = $queryBuilder->executeQuery();
                $__data = $result->fetchAllAssociative();
                return ['data' => $__data];
            } catch (TableNotFoundException $e) {
                return ['error' => $e->getMessage()];
            } catch (\Exception $e) {
                return ['error' => $e->getMessage()];
            }
        }
        return ['error' => 'Invalid Request!!'];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function storeAction(array $data): array
    {
        if (isset($data['table'])) {
            try {
                if (isset($data['values']) && is_array($data['values']) && sizeof($data['values']) > 0) {
                    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable((string)$data['table']);
                    $affectedRows = $queryBuilder
                        ->insert((string)$data['table'])
                        ->values($data['values'])
                        ->executeStatement();
                    return ['data' => $affectedRows];
                }
            } catch (TableNotFoundException $e) {
                return ['error' => $e->getMessage()];
            } catch (\Exception $e) {
                return ['error' => $e->getMessage()];
            }
        }
        return ['error' => 'Invalid Request!!'];
    }
    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function updateAction(array $data): array
    {
        if (isset($data['table'])) {
            try {
                if (isset($data['value']) && isset($data['key']) && isset($data['updates']) && is_array($data['updates']) && sizeof($data['updates']) > 0) {
                    $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable((string)$data['table']);
                    $queryBuilder->update((string)$data['table'])->where($queryBuilder->expr()->eq((string)$data['key'], $queryBuilder->createNamedParameter($data['value'])));
                    foreach ($data['updates'] as $value) {
                        $queryBuilder->set($value['key'], $value['val']);
                    }
                    $result = $queryBuilder->executeStatement();
                    return ['data' => $result];
                } else {
                    return ['error' => 'Parameters Missing!!'];
                }
            } catch (TableNotFoundException $e) {
                return ['error' => $e->getMessage()];
            } catch (\Exception $e) {
                return ['error' => $e->getMessage()];
            }
        }
        return ['error' => 'Invalid Request!!'];
    }
    /**
     * @param array<string, \Psr\Http\Message\UploadedFileInterface> $uploadedd
     * @return array<string, mixed>
     */
    public function uploadAction(array $uploadedd): array
    {
        $updated_paths = [];

        if (sizeof($uploadedd) > 0) {
            $ext_pub = 'EXT:t3themeextend/Resources/Public/';
            $_extpath = GeneralUtility::getFileAbsFileName($ext_pub);

            foreach ($uploadedd as $key => $value) {
                $src = $value;
                $directory = '';

                if ($key == 'favicon' || $key == 'logo') {
                    $directory = 'images/';
                } else {
                    if (strpos($key, '_js_') !== false) {
                        $directory = 'js/';
                    }
                    if (strpos($key, '_css_') !== false) {
                        $directory = 'css/';
                    }
                    if (strpos($key, '_font_') !== false) {
                        $directory = 'fonts/';
                    }
                }
                $dir = $_extpath . $directory;
                GeneralUtility::mkdir_deep($dir);
                $updated_paths[$key]['extpath'] = $ext_pub . $directory . pathinfo($src->getClientFilename() ?? '', PATHINFO_BASENAME);
                $updated_paths[$key]['public'] = \TYPO3\CMS\Core\Utility\PathUtility::getAbsoluteWebPath($_extpath) . $directory . pathinfo($src->getClientFilename() ?? '', PATHINFO_BASENAME);
                $updated_paths[$key]['filename'] = $src->getClientFilename();
                $srcFileName = $dir . '/' . pathinfo($src->getClientFilename() ?? '', PATHINFO_BASENAME);

                if ($src instanceof \TYPO3\CMS\Core\Http\UploadedFile) {
                    $stream = $src->getStream();
                    $handle = fopen($srcFileName, 'wb+');
                    if ($handle === false)
                        return ['error' => 'Could not open file handle'];
                    $stream->rewind();
                    while (!$stream->eof()) {
                        $bytes = $stream->read(4096);
                        fwrite($handle, $bytes);
                    }
                    fclose($handle);
                }
            }
            return ['uploads' => $updated_paths];
        } else {
            return ['uploads' => $updated_paths];
        }
    }
    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function fetchFileInfoAction(array $data): array
    {
        if (!isset($data['content']) || !is_array($data['content']) || count($data['content']) === 0) {
            return ['status' => 0, 'msg' => "Something went wrong !"];
        }

        $__datafile = [];
        $fileswritten = 0;
        $extPath = ExtensionManagementUtility::extPath('t3themeextend');
        $publicPath = \TYPO3\CMS\Core\Core\Environment::getPublicPath();

        foreach ($data['content'] as $value) {
            $relativePath = ltrim((string)$value['path'], '/');
            $filePath = $publicPath . '/' . $relativePath;
            if (!file_exists($filePath)) {
                $filePath = $extPath . $relativePath;
            }

            if (file_exists($filePath)) {
                $fileInfo = stat($filePath);
                if ($fileInfo === false) {
                    $__datafile[$value['path']] = [
                        "path" => $value['path'],
                        "error" => "could not stat file"
                    ];
                } else {
                    $__datafile[$value['path']] = [
                        "path" => $value['path'],
                        "size" => $fileInfo['size'],
                        "modified" => date('Y-m-d H:i:s', $fileInfo['mtime'])
                    ];
                }
            } else {
                $__datafile[$value['path']] = [
                    "path" => $value['path'],
                    "error" => "file not exist"
                ];
            }

            $fileswritten++;
        }

        if ($fileswritten === count($data['content'])) {
            return ['status' => 1, 'data' => $__datafile];
        }

        return ['status' => 0, 'msg' => "Something went wrong !"];
    }
}
