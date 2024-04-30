<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance\Controllers;

use SimpleSAML\Configuration;
use SimpleSAML\Error\ConfigurationError;
use SimpleSAML\Error\Exception;
use SimpleSAML\Metadata\MetaDataStorageHandlerPdo;
use SimpleSAML\Module;
use SimpleSAML\Module\conformance\Authorization;
use SimpleSAML\Module\conformance\Errors\AuthorizationException;
use SimpleSAML\Module\conformance\Factories\GenericStatusFactory;
use SimpleSAML\Module\conformance\Factories\TemplateFactory;
use SimpleSAML\Module\conformance\GenericStatus;
use SimpleSAML\Module\conformance\ModuleConfiguration;
use SimpleSAML\Module\conformance\SspBridge;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

use function noop;

class Metadata
{
    public function __construct(
        protected Configuration $sspConfig,
        protected ModuleConfiguration $moduleConfiguration,
        protected MetaDataStorageHandlerPdo $metaDataStorageHandlerPdo,
        protected SspBridge $sspBridge,
        protected GenericStatusFactory $genericStatusFactory,
        protected TemplateFactory $templateFactory,
        protected Authorization $authorization,
    ) {
    }

    /**
     * @throws ConfigurationError|Exception
     * @throws AuthorizationException
     */
    public function add(Request $request): Response
    {
        $this->authorization->requireSimpleSAMLphpAdmin(true);

        $status = $this->genericStatusFactory->fromRequest($request);
        $template = $this->templateFactory->build(
            ModuleConfiguration::MODULE_NAME . ':metadata/add.twig',
            Module\conformance\Helpers\Routes::PATH_METADATA_ADD,
            $status
        );

        $template->data += [
            'xmlData' => null,
        ];

        return $template;
    }

    /**
     * @throws AuthorizationException
     */
    public function persist(Request $request): Response
    {
        $this->authorization->requireAdministrativeToken($request);

        $xmlData = $this->getXmlData($request);

        $requestStatus = $this->genericStatusFactory->fromRequest($request);

        if (empty($xmlData)) {
            $requestStatus->setStatusError()->setMessage(noop('No XML data provided.'));
            return $this->prepareResponse($request, $requestStatus, 400);
        }

        try {
            $this->sspBridge->utils()->xml()->checkSAMLMessage($xmlData, 'saml-meta');
        } catch (Exception $exception) {
            $requestStatus->setStatusError()->setMessage(noop('Invalid XML. ') . $exception->getMessage());
            return $this->prepareResponse($request, $requestStatus, 400);
        }

        try {
            $entities = $this->sspBridge->metadata()->samlParser()->parseDescriptorsString($xmlData);
        } catch (Throwable $exception) {
            $requestStatus->setStatusError()->setMessage(noop('Error parsing XML. ') . $exception->getMessage());
            return $this->prepareResponse($request, $requestStatus, 400);
        }

        $spEntities = [];
        foreach ($entities as $entity) {
            if ($spEntity = $entity->getMetadata20SP()) {
                unset($spEntity['entityDescriptor']);
                unset($spEntity['expire']);
                if (empty($spEntity['entityid'])) {
                    continue;
                }
                $this->metaDataStorageHandlerPdo->addEntry(
                    (string)$spEntity['entityid'],
                    SspBridge::KEY_SET_SP_REMOTE,
                    $spEntity
                );
                $spEntities[] = $spEntity;
            }
        }

        if (empty($spEntities)) {
            $requestStatus->setStatusOk()->setMessage(noop('XML parsed, but no SP metadata found.'));
        } else {
            $requestStatus->setStatusOk()->setMessage(
                sprintf(noop('Imported/Updated metadata for %s SPs.'), count($spEntities))
            );
        }

        return $this->prepareResponse($request, $requestStatus);
    }

    protected function prepareResponse(Request $request, GenericStatus $requestStatus, int $httpStatus = 200): Response
    {
        if ($request->request->has('fromUi')) {
            return new RedirectResponse(
                $this->sspBridge->module()->getModuleURL(
                    ModuleConfiguration::MODULE_NAME . '/metadata/add',
                    $requestStatus->toArray()
                )
            );
        }

        return new JsonResponse($requestStatus->toArray(), $httpStatus);
    }

    protected function getXmlData(Request $request): ?string
    {
        /** @var ?UploadedFile $xmlFile */
        $xmlFile = $request->files->get('xmlFile');

        if ($xmlFile) {
            return trim(file_get_contents($xmlFile->getPathname()));
        } elseif ($xmlData = $request->request->get('xmlData')) {
            return trim((string)$xmlData);
        }

        return null;
    }
}
