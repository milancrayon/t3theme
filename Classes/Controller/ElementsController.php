<?php
declare(strict_types=1);
namespace Crayon\T3theme\Controller;

use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Core\Page\PageRenderer;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\HtmlResponse;

#[AsController]
class ElementsController extends ActionController
{
    public function __construct(
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        protected readonly PageRenderer $pageRenderer,
    ) {
    }

    public function indexAction(): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $moduleTemplate->setTitle('t3theme');

        $this->pageRenderer->addCssFile(
            'https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700',
            'stylesheet',
            'all',
            '',
            false
        );

        $this->pageRenderer->addHeaderData(
            '<link rel="preconnect" href="https://fonts.googleapis.com">'
        );
        $this->pageRenderer->addHeaderData(
            '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'
        );

        $this->pageRenderer->addCssFile(
            'EXT:t3theme/Resources/Public/t3theme/index-DPPTz_7j.css'
        );
        $this->pageRenderer->addJsFile(
            'EXT:t3theme/Resources/Public/t3theme/index-CkXKmRd3.js',
            'module'
        );


        return new HtmlResponse(
            $moduleTemplate->render('Elements/Index')
        );
    }
}
