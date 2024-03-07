<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance\Controllers;

use SimpleSAML\Configuration;
use SimpleSAML\Module\conformance\Authorization;
use SimpleSAML\Module\conformance\Database\Migrator;
use SimpleSAML\Module\conformance\GenericStatus;
use SimpleSAML\Module\conformance\GenericStatusFactory;
use SimpleSAML\Module\conformance\Helpers;
use SimpleSAML\Module\conformance\Helpers\Routes;
use SimpleSAML\Module\conformance\ModuleConfiguration;
use SimpleSAML\Module\conformance\NucleiEnv;
use SimpleSAML\Module\conformance\SspBridge;
use SimpleSAML\Module\conformance\TemplateFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Overview
{
    public function __construct(
        protected Configuration $sspConfig,
        protected ModuleConfiguration $moduleConfiguration,
        protected TemplateFactory $templateFactory,
        protected Authorization $authorization,
        protected Migrator $migrator,
        protected GenericStatusFactory $genericStatusFactory,
        protected SspBridge $sspBridge,
        protected NucleiEnv $nucleiEnv,
        protected Helpers $helpers,
    ) {
        $this->authorization->requireSimpleSAMLphpAdmin(true);
    }

    public function index(Request $request): Response
    {
        $status = $this->genericStatusFactory->fromRequest($request);

        /** @psalm-suppress ForbiddenCode */
        $nucleiStatus = shell_exec(
            "cd {$this->nucleiEnv->dataDir}; " .
            "nuclei --version 2>&1"
        );

        $nucleiStatus = $nucleiStatus ?
            $this->helpers->shell()->replaceColorCodes(trim($nucleiStatus)) :
            null;

        $template = $this->templateFactory->build(
            ModuleConfiguration::MODULE_NAME . ':overview/index.twig',
            Routes::PATH_OVERVIEW_INDEX,
            $status
        );

        $template->data['migrator'] = $this->migrator;
        $template->data['nucleiStatus'] = $nucleiStatus;

        return $template;
    }

    public function runMigrations(Request $request): Response
    {
        $status = $this->genericStatusFactory->fromRequest($request);

        try {
            $this->migrator->runNonImplementedMigrations();
            $status->setStatusOk()->setMessage('Migrations implemented.');
        } catch (\Throwable $exception) {
            $status->setStatusError()->setMessage('Error while running migrations: ' . $exception->getMessage());
        }

        return new RedirectResponse(
            $this->sspBridge->module()->getModuleURL(
                ModuleConfiguration::MODULE_NAME . '/overview',
                $status->toArray()
            )
        );
    }
}
