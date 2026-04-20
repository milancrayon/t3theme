<?php

namespace Crayon\T3theme\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\StreamFactoryInterface;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Psr\Http\Message\ResponseFactoryInterface;
use Crayon\T3theme\Service\BaseService; 
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;

class PageResolver implements MiddlewareInterface
{

    protected int $limit = 100;
    protected int $window = 3600;
 
    public function __construct(
        private readonly LanguageServiceFactory $languageServiceFactory,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly StreamFactoryInterface $streamFactory,
    ) {
    }
 
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (strtoupper($request->getMethod()) === 'OPTIONS') {
            return $this->responseFactory->createResponse(204)
                ->withHeader('Access-Control-Allow-Origin', '*')
                ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS')
                ->withHeader('Access-Control-Allow-Headers', 'Origin, X-Requested-With, Content-Type, Accept, Authorization, Cache-Control')
                ->withHeader('Access-Control-Allow-Credentials', 'true')
                ->withHeader('Access-Control-Max-Age', '86400');
        }
        
        $ip = $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown';
        $cache = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class)
            ->getCache('cratelimit');

        $slug = $request->getUri()->getPath();
        $cacheKey = 'ratelimit_' . md5($ip . '_' . $slug);


         $data = $cache->get($cacheKey) ?? ['count' => 0, 'timestamp' => time()];

        if ($data) {
            if ($data['timestamp']) {
                if (time() - $data['timestamp'] > $this->window) {
                    $data = ['count' => 0, 'timestamp' => time()];
                }
            }
            $data['count']++;
            if ($data['count'] > $this->limit) {
                return new JsonResponse([
                    'error' => 'Rate limit exceeded',
                    'retry_after' => $this->window - (time() - $data['timestamp'])
                ], 429);

            }
            $cache->set($cacheKey, $data, [], $this->window);
        } 

        try {
            $site = $request->getAttribute('site');
            if (!$site instanceof \TYPO3\CMS\Core\Site\Entity\Site) {
                $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
                $allSites = $siteFinder->getAllSites();
                $host = $request->getUri()->getHost();
                $site = null;
                foreach ($allSites as $possibleSite) {
                    if ($possibleSite->getBase()->getHost() === $host) {
                        $site = $possibleSite;
                        break;
                    }
                }
                if ($site === null) {
                    throw new SiteNotFoundException('No site found for host ' . $host);
                }
            }
            $rootPageId = $site->getRootPageId();
        } catch (SiteNotFoundException $e) {
            $rootPageId = 1; // fallback root page ID
            $site = null;
        }

        $queryString = $request->getQueryParams();
        $method = strtolower($request->getMethod());
        if (isset($queryString['params'])) {
            $slug = $queryString['params'];
        } else {
            $slug = "";
        }
        if (isset($_SERVER["CONTENT_LENGTH"])) {
            $expectedLength = intval($_SERVER["CONTENT_LENGTH"]);
        } else {
            $expectedLength = 0;
        }
        $rawInput = file_get_contents('php://input', false, stream_context_get_default(), 0, $expectedLength);
        if ($rawInput === false) {
            $rawInput = '';
        }
        $bodyData = json_decode($rawInput, true);
        $bsService = GeneralUtility::makeInstance(BaseService::class);
        $response = $this->responseFactory->createResponse()
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Max-Age', '86400')
            ->withHeader('Cache-Control', 'public, max-age=86400,no-store, no-cache, must-revalidate, post-check=0, pre-check=0, false')
            ->withHeader('Vary', 'origin')
            ->withHeader(
                'Access-Control-Allow-Headers',
                $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'] ?? 'Origin, X-Requested-With, Content-Type, Authorization, Cache-Control',
            )
            ->withHeader('Access-Control-Allow-Credentials', 'true')
            ->withHeader('Allow', 'GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS')
            ->withHeader('Pragma', 'no-cache')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS')
        ; 
        $uploadedData = $request->getUploadedFiles();  
        if ($method === "get" && ($slug === "version" || $slug === "version/")) {
            $responSControl = $bsService->versionAction();
            $response->getBody()->write(json_encode($responSControl));
            return $response;
        } else if ($method === "get" && ($slug === "countries" || $slug === "countries/")) {
            $responSControl = $bsService->countryAction();
            $response->getBody()->write(json_encode($responSControl));
            return $response;
        } else if ($method === "get" && ($slug === "languages" || $slug === "languages/")) {
            $responSControl = $site ? $bsService->languagesAction($site) : ['error' => 'Site not found'];
            $response->getBody()->write(json_encode($responSControl));
            return $response;
        } else if ($method === "get" && ($slug === "formdata" || $slug === "formdata/")) {
            if (isset($queryString['uid'])) {
                $responSControl = $bsService->formDataAction((int)$queryString['uid']);
                $response->getBody()->write(json_encode($responSControl));
            } else {
                $response->getBody()->write(json_encode(['error' => "slug Missing!!"]));
            }
            return $response;
        } else if ($method === "get" && ($slug === "flexform" || $slug === "flexform/")) {
            if (isset($queryString['uid'])) {
                $responSControl = $bsService->fetchFlexFormAction((int)$queryString['uid']);
                $response->getBody()->write(json_encode($responSControl));
            } else {
                $response->getBody()->write(json_encode(['error' => "slug Missing!!"]));
            }
            return $response;
        } else if ($method === "post" && ($slug === "dbelements" || $slug === "dbelements/")) {
            $responSControl = $bsService->elementDatabaseAction($bodyData);
            $response->getBody()->write(json_encode($responSControl));
            return $response;
        } else if ($method === "post" && ($slug === "filedata" || $slug === "filedata/")) {
            $responSControl = $bsService->fetchFileDataAction($bodyData);
            $response->getBody()->write(json_encode($responSControl));
            return $response;
        } else if ($method === "post" && ($slug === "fileinfo" || $slug === "fileinfo/")) {
            $responSControl = $bsService->fetchFileInfoAction($bodyData);
            $response->getBody()->write(json_encode($responSControl));
            return $response;
        } else if ($method === "post" && ($slug === "removefile" || $slug === "removefile/")) {
            $responSControl = $bsService->removeFileAction($bodyData);
            $response->getBody()->write(json_encode($responSControl));
            return $response;
        } else if ($method === "post" && ($slug === "updatefile" || $slug === "updatefile/")) {
            $responSControl = $bsService->fileUpdationAction($bodyData);
            $response->getBody()->write(json_encode($responSControl));
            return $response;
        } else if ($method === "post" && ($slug === "getdata" || $slug === "getdata/")) {
            $responSControl = $bsService->resultAction($bodyData);
            $response->getBody()->write(json_encode($responSControl));
            return $response;
        } else if ($method === "post" && ($slug === "storedata" || $slug === "storedata/")) {
            $responSControl = $bsService->storeAction($bodyData);
            $response->getBody()->write(json_encode($responSControl));
            return $response;
        } else if ($method === "post" && ($slug === "updatedata" || $slug === "updatedata/")) {
            $responSControl = $bsService->updateAction($bodyData);
            $response->getBody()->write(json_encode($responSControl));
            return $response;
        } else if ($method === "post" && ($slug === "upload" || $slug === "upload/")) {
            $responSControl = $bsService->uploadAction($uploadedData);
            $response->getBody()->write(json_encode($responSControl));
            return $response;
        } else {
            return $handler->handle($request); 
        } 
    } 
}