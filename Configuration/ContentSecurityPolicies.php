<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Directive;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Mutation;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationCollection;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationMode;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Scope;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\SourceKeyword;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\UriValue;
use TYPO3\CMS\Core\Type\Map;

return Map::fromEntries([
    Scope::backend(),
    new MutationCollection(
        new Mutation(
            MutationMode::Set,
            Directive::DefaultSrc,
            SourceKeyword::self
        ),
        new Mutation(
            MutationMode::Set,
            Directive::WorkerSrc,
            SourceKeyword::self
        ),
        new Mutation(
            MutationMode::Append,
            Directive::StyleSrc,
            SourceKeyword::self,
            new UriValue("'unsafe-inline'"),
            new UriValue('https://fonts.googleapis.com'),
            new UriValue('https://cdn.jsdelivr.net'),
        ),
        new Mutation(
            MutationMode::Append,
            Directive::FontSrc,
            SourceKeyword::self,
            new UriValue('https://fonts.gstatic.com'),
            new UriValue('https://cdn.jsdelivr.net'),
        ),
        new Mutation(
            MutationMode::Append,
            Directive::ConnectSrc,
            SourceKeyword::self,
            new UriValue('https://api.t3api.com'),
            new UriValue('https://cdn.jsdelivr.net'),
        ),
        new Mutation(
            MutationMode::Append,
            Directive::ScriptSrc,
            SourceKeyword::self,
            new UriValue("'unsafe-inline'"),  
            new UriValue('https://cdnjs.cloudflare.com'),
            new UriValue('https://cdn.jsdelivr.net'),
        ),
         new Mutation(
            MutationMode::Append,
            Directive::ImgSrc,
            SourceKeyword::self,
            new UriValue('https://api.t3api.com')
        ),

        
    ),
]);
