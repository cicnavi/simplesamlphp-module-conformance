<?php

namespace SimpleSAML\Module\conformance\Controllers;

use phpDocumentor\Reflection\DocBlock\Tags\Generic;
use SimpleSAML\Configuration;
use SimpleSAML\Error\ConfigurationError;
use SimpleSAML\Error\Exception;
use SimpleSAML\Metadata\MetaDataStorageHandlerPdo;
use SimpleSAML\Metadata\SAMLParser;
use SimpleSAML\Module;
use SimpleSAML\Module\conformance\ModuleConfig;
use SimpleSAML\Module\conformance\GenericStatus;
use SimpleSAML\Utils\XML;
use SimpleSAML\XHTML\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Metadata
{
    const SET_SAML20_SP_REMOTE = 'saml20-sp-remote';

    public function __construct(
        protected Configuration $sspConfig,
        protected ModuleConfig $moduleConfig,
        protected MetaDataStorageHandlerPdo $metaDataStorageHandlerPdo,
        protected XML $xmlUtils,
    ) {
    }

    /**
     * @throws ConfigurationError
     */
    public function add(Request $request): Response
    {
        $status = GenericStatus::fromRequest($request);
        $t = new Template($this->sspConfig, 'conformance:metadata_add.twig');
        $t->data = [
            'xmlData' => null,
            ...$status->toArray(),
        ];

        return $t;
    }

    public function persist(Request $request): Response
    {
        $xmlData = $this->getXmlData($request);

        $requestStatus = new GenericStatus();

        if (empty($xmlData)) {
            $requestStatus->setStatusError()->setMessage('No XML data provided.');
            return $this->prepareResponse($request, $requestStatus, 400);
        }

        try {
            $this->xmlUtils->checkSAMLMessage($xmlData, 'saml-meta');
        } catch (Exception $exception) {
            $requestStatus->setStatusError()->setMessage('Invalid XML. ' . $exception->getMessage());
            return $this->prepareResponse($request, $requestStatus, 400);
        }

        try {
            // TODO mivanci Create injected bridge.
            $entities = SAMLParser::parseDescriptorsString($xmlData);
        } catch (Exception $exception) {
            $requestStatus->setStatusError()->setMessage('Error parsing XML. ' . $exception->getMessage());
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
                    $spEntity['entityid'],
                    self::SET_SAML20_SP_REMOTE,
                    $spEntity
                );
                $spEntities[] = $spEntity;
            }
        }

        if (empty($spEntities)) {
            $requestStatus->setStatusOk()->setMessage('XML parsed, but no SP metadata found.');
        } else {
            $requestStatus->setStatusOk()->setMessage(sprintf('Imported metadata for %s SPs.', count($spEntities)));
        }

        return $this->prepareResponse($request, $requestStatus);
    }

    protected function prepareResponse(Request $request, GenericStatus $requestStatus, int $httpStatus = 200): Response
    {
        if ($request->request->has('fromUi')) {
            return new RedirectResponse(
                Module::getModuleURL(ModuleConfig::MODULE_NAME . '/metadata/add', $requestStatus->toArray())
            );
        }

        return new JsonResponse($requestStatus->toArray(),$httpStatus);
    }

    protected function getXmlData(Request $request): ?string
    {
        if ($xmlFile = $request->files->get('xmlFile')) {
            return trim(file_get_contents($xmlFile->getPathname()));
        } elseif ($xmlData = $request->request->get('xmlData')) {
            return trim($xmlData);
        }

        return null;
    }
}