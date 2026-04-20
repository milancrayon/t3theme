<?php

declare(strict_types=1);

namespace Crayon\T3theme\Middleware;

use Crayon\T3theme\Domain\Repository\ThemeconfigRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Localization\Locale;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Crayon\T3theme\Domain\Model\Themeconfig;

final class LanguageDetection implements MiddlewareInterface
{
    private const COOKIE_NAME = 'lang_redirect_done';

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {

        $repository = GeneralUtility::makeInstance(ThemeconfigRepository::class);

        /** @var Themeconfig|null $records */
        $records = $repository->findByUid(1);
        if ($records instanceof Themeconfig) {
            $langm = $records->getLangm();
            if ($langm !== '') {
                $langm_data = json_decode($langm, true);
                if (is_array($langm_data) && isset($langm_data['auto_lang_detect']) && $langm_data['auto_lang_detect']) { 
                 
                    if ($request->getUri()->getPath() !== '/') {
                        return $handler->handle($request);
                    }

                    if (!empty($_COOKIE[self::COOKIE_NAME])) {
                        return $handler->handle($request);
                    }

                    $acceptLanguage = $request->getHeaderLine('Accept-Language');

                    if ($acceptLanguage === '') {
                        return $handler->handle($request);
                    }

                    $site = $request->getAttribute('site');
                    if (!$site) {
                        return $handler->handle($request);
                    }
                    $preferredLanguages = $this->parseAcceptLanguage($acceptLanguage);

                    foreach ($preferredLanguages as $preferredIso) {
                        foreach ($site->getLanguages() as $language) {
                            $isoCode = $this->getIsoCodeFromLanguage($language);

                            if ($isoCode === $preferredIso) {
                                $response = new RedirectResponse(
                                    (string) $language->getBase(),
                                    302
                                );

                                return $response->withAddedHeader(
                                    'Set-Cookie',
                                    self::COOKIE_NAME . '=1; Path=/; Max-Age=3600'
                                );
                            }
                        }
                    }
                }
            }

            return $handler->handle($request);
        }

        return $handler->handle($request);
    }

    private function getIsoCodeFromLanguage(SiteLanguage $language): string
    {
        $locale = $language->getLocale();
        return strtolower($locale->getLanguageCode());
    }
    /**
     * @param string $header
     * @return array<int, string>
     */
    private function parseAcceptLanguage(string $header): array
    {
        $languages = [];

        foreach (explode(',', $header) as $part) {
            $lang = strtolower(substr(trim($part), 0, 2));
            if (preg_match('/^[a-z]{2}$/', $lang)) {
                $languages[] = $lang;
            }
        }

        return array_unique($languages);
    }
}
