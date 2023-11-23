<?php

namespace SimpleSAML\Module\conformance\Controllers;

use SimpleSAML\Configuration;
use SimpleSAML\Error\Exception;
use SimpleSAML\Metadata\MetaDataStorageHandlerPdo;
use SimpleSAML\Metadata\SAMLParser;
use SimpleSAML\Module\conformance\ModuleConfig;
use SimpleSAML\Utils\XML;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Metadata
{
    public function __construct(
        protected Configuration $sspConfig,
        protected ModuleConfig $moduleConfig,
        protected MetaDataStorageHandlerPdo $metaDataStorageHandlerPdo,
        protected XML $xmlUtils,
    ) {
    }

    public function add(Request $request): Response
    {
        $xmlData = $this->getXmlData($request);

        if (empty($xmlData)) {
            return new JsonResponse(['status' => 'error', 'message' => 'No XML data provided.',], 400);
        }

        try {
            $this->xmlUtils->checkSAMLMessage($xmlData, 'saml-meta');
        } catch (Exception $exception) {
            return new JsonResponse(
                ['status' => 'error', 'message' => 'Invalid XML. ' . $exception->getMessage(),],
                400
            );
        }

        try {
            // TODO mivanci Create injected bridge.
            $entities = SAMLParser::parseDescriptorsString($xmlData);
        } catch (Exception $exception) {
            return new JsonResponse(
                ['status' => 'error', 'message' => 'Error parsing XML. ' . $exception->getMessage(),],
                400
            );
        }

        // TODO mivanci continue

        die(var_dump($entities));
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