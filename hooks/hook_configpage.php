<?php

declare(strict_types=1);

use SimpleSAML\Locale\Translate;
use SimpleSAML\Module\conformance\Helpers\Routes;
use SimpleSAML\Module\conformance\ModuleConfig;
use SimpleSAML\XHTML\Template;

/** @noinspection PhpParameterByRefIsNotUsedAsReferenceInspection Reference is used by SimpleSAMLphp */
function conformance_hook_configpage(Template &$template): void
{
    $moduleRoutesHelper = new Routes();

    $dataLinksKey = 'links';

    if (!isset($template->data[$dataLinksKey]) || !is_array($template->data[$dataLinksKey])) {
        return;
    }

    $template->data[$dataLinksKey][] = [
        'href' => $moduleRoutesHelper->getUrl(Routes::PATH_METADATA_ADD),
        'text' => Translate::noop('Conformance: Add SP metadata'),
    ];

    $template->getLocalization()->addModuleDomain(ModuleConfig::MODULE_NAME);
}
