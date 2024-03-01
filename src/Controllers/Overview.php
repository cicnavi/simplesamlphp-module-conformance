<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance\Controllers;

use SimpleSAML\Configuration;
use SimpleSAML\Module\conformance\Authorization;
use SimpleSAML\Module\conformance\ModuleConfiguration;
use SimpleSAML\Module\conformance\TemplateFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Overview
{
    public function __construct(
        protected Configuration $sspConfig,
        protected ModuleConfiguration $moduleConfiguration,
        protected TemplateFactory $templateFactory,
        protected Authorization $authorization,
    ) {
    }

//    public function index(Request $request): Response
//    {
//    }
}
