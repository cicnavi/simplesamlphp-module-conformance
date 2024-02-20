<?php

namespace SimpleSAML\Module\conformance;

use SimpleSAML\Configuration;
use SimpleSAML\Error\ConfigurationError;
use SimpleSAML\Error\Exception;
use SimpleSAML\Module\conformance\Helpers\Routes;
use SimpleSAML\XHTML\Template;

class TemplateFactory
{
    protected bool $showMenu = true;
    protected bool $includeDefaultMenuItems = true;
    protected ?string $activeHrefPath = null;

    public function __construct(
        protected Configuration $sspConfiguration,
        protected ModuleConfiguration $moduleConfiguration,
        protected Menu $menu,
        protected Routes $routes,
    ) {
    }

    /**
     * @throws ConfigurationError|Exception
     */
    public function build(string $template, string $activeHrefPath = null): Template
    {
        $template = new Template($this->sspConfiguration, $template);
        $template->getLocalization()->addModuleDomain(ModuleConfiguration::MODULE_NAME);

        if ($this->includeDefaultMenuItems) {
            $this->includeDefaultMenuItems();
        }

        if ($activeHrefPath) {
            $this->setActiveHrefPath($activeHrefPath);
        }

        $template->data = [
            'sspConfiguration' => $this->sspConfiguration,
            'moduleConfiguration' => $this->moduleConfiguration,
            'menu' => $this->menu,
            'showMenu' => $this->showMenu,
        ];

        return $template;
    }

    /**
     * @throws Exception
     */
    protected function includeDefaultMenuItems(): void
    {
        // TODO mivanci Add an Overview item.

        $this->menu->addItem(
            $this->menu->buildItem(
                $this->generateFullHrefPath(Routes::PATH_TEST_NUCLEI_SETUP),
                \SimpleSAML\Locale\Translate::noop('Run Nuclei Test'),
            )
        );

        $this->menu->addItem(
            $this->menu->buildItem(
                $this->generateFullHrefPath(Routes::PATH_TEST_RESULTS),
                \SimpleSAML\Locale\Translate::noop('Nuclei Results'),
            )
        );

        $this->menu->addItem(
            $this->menu->buildItem(
                $this->generateFullHrefPath(Routes::PATH_METADATA_ADD),
                \SimpleSAML\Locale\Translate::noop('Add SP Metadata'),
            )
        );

        $this->menu->addItem(
            $this->menu->buildItem(
                $this->routes->getUrl('admin', null),
                \SimpleSAML\Locale\Translate::noop('Back to SSP'),
            )
        );
    }

    public function generateFullHrefPath(string $path): string
    {
        return $this->routes->getUrl($path);
    }

    public function setShowMenu(bool $showMenu): TemplateFactory
    {
        $this->showMenu = $showMenu;
        return $this;
    }

    public function setIncludeDefaultMenuItems(bool $includeDefaultMenuItems): TemplateFactory
    {
        $this->includeDefaultMenuItems = $includeDefaultMenuItems;
        return $this;
    }

    public function setActiveHrefPath(?string $activeHrefPath): TemplateFactory
    {
        $this->activeHrefPath = $activeHrefPath ? $this->generateFullHrefPath($activeHrefPath) : null;
        return $this;
    }
}
