<?php

declare(strict_types=1);

use SimpleSAML\Locale\Translate;
use SimpleSAML\Module\conformance\Helpers\Routes;
use SimpleSAML\Module\conformance\ModuleConfiguration;
use SimpleSAML\XHTML\Template;

/**
 * @noinspection PhpParameterByRefIsNotUsedAsReferenceInspection Reference is used by SimpleSAMLphp
 * @noinspection PhpUnused
 * @throws \SimpleSAML\Error\Exception
 */
function conformance_hook_configpage(Template &$template): void
{
    $moduleRoutesHelper = new Routes();

    $dataLinksKey = 'links';

    if (!isset($template->data[$dataLinksKey]) || !is_array($template->data[$dataLinksKey])) {
        return;
    }

    $template->data[$dataLinksKey][] = [
        'href' => $moduleRoutesHelper->getUrl(Routes::PATH_TEST_NUCLEI_SETUP),
        'text' => Translate::noop('Conformance'),
    ];

    $template->getLocalization()->addModuleDomain(ModuleConfiguration::MODULE_NAME);
}
