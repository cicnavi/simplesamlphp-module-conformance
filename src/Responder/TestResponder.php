<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance\Responder;

use DOMNodeList;
use Exception;
use Psr\Log\LoggerInterface;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use SAML2\Assertion;
use SAML2\Binding;
use SAML2\Constants;
use SAML2\DOMDocumentFactory;
use SAML2\EncryptedAssertion;
use SAML2\XML\ds\KeyInfo;
use SAML2\XML\ds\X509Certificate;
use SAML2\XML\ds\X509Data;
use SAML2\XML\saml\AttributeValue;
use SAML2\XML\saml\Issuer;
use SAML2\XML\saml\NameID;
use SAML2\XML\saml\SubjectConfirmation;
use SAML2\XML\saml\SubjectConfirmationData;
use SimpleSAML\Assert\Assert;
use SimpleSAML\Compat\Logger;
use SimpleSAML\Error;
use SimpleSAML\Logger as StaticLogger;
use SimpleSAML\Configuration;
use SimpleSAML\IdP;
use SimpleSAML\Module\conformance\ModuleConfiguration;
use SimpleSAML\Module\saml\IdP\SAML2;
use SimpleSAML\Module\saml\Message;
use SimpleSAML\Stats;
use SimpleSAML\Utils\HTTP;
use SimpleSAML\Utils\Random;

/**
 * This class extends existing SAML2 responder and prepares invalid responses.
 * @psalm-suppress all
 */
class TestResponder extends SAML2 implements ResponderInterface
{
    protected Configuration $sspConfig;
    protected ModuleConfiguration $moduleConfiguration;
    protected LoggerInterface $logger;
    protected HTTP $httpUtil;
    protected Random $randomUtil;

    protected ?Configuration $spMetadata = null;
    protected ?string $spEntityId = null;
    protected ?string $requestId = null;
    protected ?string $relayState = null;
    protected ?string $consumerUrl = null;
    protected ?string $protocolBinding = null;
    protected ?IdP $idp = null;
    protected ?Configuration $idpMetadata = null;


    public function __construct(
        Configuration $sspConfig = null,
        ModuleConfiguration $moduleConfig = null,
        LoggerInterface $logger = null,
        HTTP $httpUtil = null,
        Random $randomUtil = null,
    ) {
        $this->sspConfig = $sspConfig ?? Configuration::getInstance();
        $this->moduleConfiguration = $moduleConfig ?? new ModuleConfiguration();
        $this->logger = $logger ?? new Logger();
        $this->httpUtil = $httpUtil ?? new HTTP();
        $this->randomUtil = $randomUtil ?? new Random();
    }

    public function standardResponse(array $state): void
    {
//        die(var_dump($state));
//        $state['IdPMetadata']['entityid'] .= 'invalid';
//        $state['core:IdP'] .= 'invalid';
        parent::sendResponse($state);
    }

    public function noSignature(array $state): void
    {
        $this->logger->info('Start response: ' . __METHOD__);

        $this->validate($state);
        $this->initialize($state);

        $this->logger->info('Sending SAML 2.0 Response to ' . var_export($this->spEntityId, true));

        $assertion = $this->buildCustomAssertion($this->idpMetadata, $this->spMetadata, $state, false);

        $this->createAssociation($assertion, $state);

        // maybe encrypt the assertion
        $assertion = self::encryptAssertion($this->idpMetadata, $this->spMetadata, $assertion);

        // create the response
        $response = $this->buildResponse($this->idpMetadata, $this->spMetadata, $this->consumerUrl, $assertion, false);

        $this->doStats($state);

        $this->sendResponseUsingBinding($response);
    }

    public function invalidSignature(array $state): void
    {
        $this->logger->info('Start response: ' . __METHOD__);
        $this->validate($state);

        // Set the dummy (wrong) private key to use for signature.
        $state['SPMetadata']['signature.privatekey'] = $this->moduleConfiguration->getDummyPrivateKey();

        $this->initialize($state);
        $this->logger->info('Sending SAML 2.0 Response to ' . var_export($this->spEntityId, true));

        $assertion = $this->buildCustomAssertion($this->idpMetadata, $this->spMetadata, $state, false);

        $this->createAssociation($assertion, $state);

        // maybe encrypt the assertion
        $assertion = self::encryptAssertion($this->idpMetadata, $this->spMetadata, $assertion);

        // create the response
        $response = $this->buildResponse($this->idpMetadata, $this->spMetadata, $this->consumerUrl, $assertion, true);

        $this->doStats($state);

        $this->sendResponseUsingBinding($response);
    }

    protected function validate(array $state): void
    {
        /** SSP start */
        Assert::keyExists($state, 'saml:RequestId'); // Can be NULL
        Assert::keyExists($state, 'saml:RelayState'); // Can be NULL.
        Assert::notNull($state['Attributes']);
        Assert::notNull($state['SPMetadata']);
        Assert::notNull($state['saml:ConsumerURL']);
        /** SSP end */

        Assert::isArray($state['SPMetadata']);
    }

    protected function initialize(array $state): void
    {
        $spMetadata = $state['SPMetadata'];
        $this->spEntityId = (string)$spMetadata['entityid'];
        $this->spMetadata = Configuration::loadFromArray(
            $spMetadata,
            '$metadata[' . var_export($this->spEntityId, true) . ']'
        );

        $this->requestId = empty($state['saml:RequestId']) ? null : (string)$state['saml:RequestId'];
        $this->relayState = empty($state['saml:RelayState']) ? null : (string)$state['saml:RelayState'];

        $this->consumerUrl = (string)$state['saml:ConsumerURL'];
        $this->protocolBinding = (string)$state['saml:Binding'];

        $this->idp = IdP::getByState($state);
        $this->idpMetadata = $this->idp->getConfig();
    }

    /**
     * Mostly copy from parent private method, with some overrides regarding signing.
     */
    protected function buildCustomAssertion(
        ?Configuration $idpMetadata,
        ?Configuration $spMetadata,
        array &$state,
        ?bool $shouldSignAssertion = null,
    ): Assertion {
        if (empty($idpMetadata) || empty($spMetadata)) {
            throw new Exception('Not all parameters were (successfully) initialized.');
        }

        Assert::notNull($state['Attributes']);
        Assert::notNull($state['saml:ConsumerURL']);

        $now = time();

        $signAssertion = $shouldSignAssertion ??
            $spMetadata->getOptionalBoolean('saml20.sign.assertion', null) ??
            $idpMetadata->getOptionalBoolean('saml20.sign.assertion', true);

        $a = new Assertion();
        if ($signAssertion) {
            Message::addSign($idpMetadata, $spMetadata, $a);
        }

        $issuer = new Issuer();
        $issuer->setValue($idpMetadata->getString('entityid'));
        $issuer->setFormat(Constants::NAMEID_ENTITY);
        $a->setIssuer($issuer);

        $audience = array_merge([$spMetadata->getString('entityid')], $spMetadata->getOptionalArray('audience', []));
        $a->setValidAudiences($audience);

        $a->setNotBefore($now - 30);

        $assertionLifetime = $spMetadata->getOptionalInteger('assertion.lifetime', null);
        if ($assertionLifetime === null) {
            $assertionLifetime = $idpMetadata->getOptionalInteger('assertion.lifetime', 300);
        }
        $a->setNotOnOrAfter($now + $assertionLifetime);

        $passAuthnContextClassRef = $this->sspConfig->getOptionalBoolean('proxymode.passAuthnContextClassRef', false);
        if (isset($state['saml:AuthnContextClassRef'])) {
            $a->setAuthnContextClassRef($state['saml:AuthnContextClassRef']);
        } elseif ($passAuthnContextClassRef && isset($state['saml:sp:AuthnContext'])) {
            // AuthnContext has been set by the upper IdP in front of the proxy, pass it back to the SP behind the proxy
            $a->setAuthnContextClassRef($state['saml:sp:AuthnContext']);
        } elseif ($this->httpUtil->isHTTPS()) {
            $a->setAuthnContextClassRef(Constants::AC_PASSWORD_PROTECTED_TRANSPORT);
        } else {
            $a->setAuthnContextClassRef(Constants::AC_PASSWORD);
        }

        $sessionStart = $now;
        if (isset($state['AuthnInstant'])) {
            $a->setAuthnInstant($state['AuthnInstant']);
            $sessionStart = $state['AuthnInstant'];
        }

        $sessionLifetime = $this->sspConfig->getOptionalInteger('session.duration', 8 * 60 * 60);
        $a->setSessionNotOnOrAfter($sessionStart + $sessionLifetime);

        $a->setSessionIndex($this->randomUtil->generateID());

        $sc = new SubjectConfirmation();
        $scd = new SubjectConfirmationData();
        $scd->setNotOnOrAfter($now + $assertionLifetime);
        $scd->setRecipient($state['saml:ConsumerURL']);
        $scd->setInResponseTo($state['saml:RequestId']);
        $sc->setSubjectConfirmationData($scd);

        // ProtcolBinding of SP's <AuthnRequest> overwrites IdP hosted metadata configuration
        $hokAssertion = null;
        if ($state['saml:Binding'] === Constants::BINDING_HOK_SSO) {
            $hokAssertion = true;
        }
        if ($hokAssertion === null) {
            $hokAssertion = $idpMetadata->getOptionalBoolean('saml20.hok.assertion', false);
        }

        if ($hokAssertion) {
            // Holder-of-Key
            $sc->setMethod(Constants::CM_HOK);

            if ($this->httpUtil->isHTTPS()) {
                if (isset($_SERVER['SSL_CLIENT_CERT']) && !empty($_SERVER['SSL_CLIENT_CERT'])) {
                    // extract certificate data (if this is a certificate)
                    $clientCert = $_SERVER['SSL_CLIENT_CERT'];
                    $pattern = '/^-----BEGIN CERTIFICATE-----([^-]*)^-----END CERTIFICATE-----/m';
                    if (preg_match($pattern, (string) $clientCert, $matches)) {
                        // we have a client certificate from the browser which we add to the HoK assertion
                        $x509Certificate = new X509Certificate();
                        $x509Certificate->setCertificate(str_replace(["\r", "\n", " "], '', $matches[1]));

                        $x509Data = new X509Data();
                        $x509Data->addData($x509Certificate);

                        $keyInfo = new KeyInfo();
                        $keyInfo->addInfo($x509Data);

                        $scd->addInfo($keyInfo);
                    } else {
                        throw new Error\Exception(
                            'Error creating HoK assertion: No valid client certificate provided during '
                            . 'TLS handshake with IdP'
                        );
                    }
                } else {
                    throw new Error\Exception(
                        'Error creating HoK assertion: No client certificate provided during TLS handshake with IdP'
                    );
                }
            } else {
                throw new Error\Exception(
                    'Error creating HoK assertion: No HTTPS connection to IdP, but required for Holder-of-Key SSO'
                );
            }
        } else {
            // Bearer
            $sc->setMethod(Constants::CM_BEARER);
        }
        $sc->setSubjectConfirmationData($scd);
        $a->setSubjectConfirmation([$sc]);

        // add attributes
        if ($spMetadata->getOptionalBoolean('simplesaml.attributes', true)) {
            $attributeNameFormat = self::getAttributeNameFormat($idpMetadata, $spMetadata);
            $a->setAttributeNameFormat($attributeNameFormat);
            $attributes = self::encodeAttributes($idpMetadata, $spMetadata, $state['Attributes']);
            $a->setAttributes($attributes);
        }

        $nameId = self::generateNameId($idpMetadata, $spMetadata, $state);
        $state['saml:idp:NameID'] = $nameId;
        $a->setNameId($nameId);

        $encryptNameId = $spMetadata->getOptionalBoolean('nameid.encryption', null);
        if ($encryptNameId === null) {
            $encryptNameId = $idpMetadata->getOptionalBoolean('nameid.encryption', false);
        }
        if ($encryptNameId) {
            $a->encryptNameId(\SimpleSAML\Module\saml\Message::getEncryptionKey($spMetadata));
        }

        if (isset($state['saml:AuthenticatingAuthority'])) {
            $a->setAuthenticatingAuthority($state['saml:AuthenticatingAuthority']);
        }

        return $a;
    }

    /**
     * Private parent method copy.
     */
    protected static function getAttributeNameFormat(
        Configuration $idpMetadata,
        Configuration $spMetadata
    ): string {
        // try SP metadata first
        $attributeNameFormat = $spMetadata->getOptionalString('attributes.NameFormat', null);
        if ($attributeNameFormat !== null) {
            return $attributeNameFormat;
        }
        $attributeNameFormat = $spMetadata->getOptionalString('AttributeNameFormat', null);
        if ($attributeNameFormat !== null) {
            return $attributeNameFormat;
        }

        // look in IdP metadata
        $attributeNameFormat = $idpMetadata->getOptionalString('attributes.NameFormat', null);
        if ($attributeNameFormat !== null) {
            return $attributeNameFormat;
        }
        $attributeNameFormat = $idpMetadata->getOptionalString('AttributeNameFormat', null);
        if ($attributeNameFormat !== null) {
            return $attributeNameFormat;
        }

        // default
        return Constants::NAMEFORMAT_URI;
    }

    /**
     * Private parent method copy.
     */
    protected static function encodeAttributes(
        Configuration $idpMetadata,
        Configuration $spMetadata,
        array $attributes
    ): array {
        $defaultEncoding = 'string';

        $srcEncodings = $idpMetadata->getOptionalArray('attributeencodings', []);
        $dstEncodings = $spMetadata->getOptionalArray('attributeencodings', []);

        /*
         * Merge the two encoding arrays. Encodings specified in the target metadata
         * takes precedence over the source metadata.
         */
        $encodings = array_merge($srcEncodings, $dstEncodings);

        $ret = [];
        foreach ($attributes as $name => $values) {
            $ret[$name] = [];
            if (array_key_exists($name, $encodings)) {
                $encoding = $encodings[$name];
            } else {
                $encoding = $defaultEncoding;
            }

            foreach ($values as $value) {
                // allow null values
                if ($value === null) {
                    $ret[$name][] = $value;
                    continue;
                }

                $attrval = $value;
                if ($value instanceof DOMNodeList) {
                    /** @psalm-suppress PossiblyNullPropertyFetch */
                    $attrval = new AttributeValue($value->item(0)->parentNode);
                }

                switch ($encoding) {
                    case 'string':
                        $value = (string) $attrval;
                        break;
                    case 'base64':
                        $value = base64_encode((string) $attrval);
                        break;
                    case 'raw':
                        if (is_string($value)) {
                            $doc = DOMDocumentFactory::fromString('<root>' . $value . '</root>');
                            /** @psalm-suppress PossiblyNullPropertyFetch */
                            $value = $doc->firstChild->childNodes;
                        }
                        Assert::isInstanceOfAny($value, [\DOMNodeList::class, \SAML2\XML\saml\NameID::class]);
                        break;
                    default:
                        throw new Error\Exception('Invalid encoding for attribute ' .
                            var_export($name, true) . ': ' . var_export($encoding, true));
                }
                $ret[$name][] = $value;
            }
        }

        return $ret;
    }

    /**
     * Private parent method copy.
     */
    protected static function generateNameId(
        Configuration $idpMetadata,
        Configuration $spMetadata,
        array $state
    ): NameID {
        StaticLogger::debug('Determining value for NameID');
        $nameIdFormat = null;

        if (isset($state['saml:NameIDFormat'])) {
            $nameIdFormat = $state['saml:NameIDFormat'];
        }

        if ($nameIdFormat === null || !isset($state['saml:NameID'][$nameIdFormat])) {
            // either not set in request, or not set to a format we supply. Fall back to old generation method
            $nameIdFormat = current($spMetadata->getOptionalArrayizeString('NameIDFormat', []));
            if ($nameIdFormat === false) {
                $nameIdFormat = current(
                    $idpMetadata->getOptionalArrayizeString('NameIDFormat', [Constants::NAMEID_TRANSIENT])
                );
            }
        }

        if (isset($state['saml:NameID'][$nameIdFormat])) {
            StaticLogger::debug(sprintf('NameID of desired format %s found in state', var_export($nameIdFormat, true)));
            return $state['saml:NameID'][$nameIdFormat];
        }

        // We have nothing else to work with, so default to transient
        if ($nameIdFormat !== Constants::NAMEID_TRANSIENT) {
            StaticLogger::notice(sprintf(
                'Requested NameID of format %s, but can only provide transient',
                var_export($nameIdFormat, true)
            ));
            $nameIdFormat = Constants::NAMEID_TRANSIENT;
        }

        $randomUtils = new Random();
        $nameIdValue = $randomUtils->generateID();

        $spNameQualifier = $spMetadata->getOptionalString('SPNameQualifier', null);
        if ($spNameQualifier === null) {
            $spNameQualifier = $spMetadata->getString('entityid');
        }

        StaticLogger::info(sprintf(
            'Setting NameID to (%s, %s, %s)',
            var_export($nameIdFormat, true),
            var_export($nameIdValue, true),
            var_export($spNameQualifier, true)
        ));
        $nameId = new NameID();
        $nameId->setFormat($nameIdFormat);
        $nameId->setValue($nameIdValue);
        $nameId->setSPNameQualifier($spNameQualifier);

        return $nameId;
    }

    protected function createAssociation(Assertion $assertion, array $state): void
    {
        $association = [
            'id'                => 'saml:' . $this->spEntityId,
            'Handler'           => '\\' . \SimpleSAML\Module\saml\IdP\SAML2::class,
            'Expires'           => $assertion->getSessionNotOnOrAfter(),
            'saml:entityID'     => $this->spEntityId,
            'saml:NameID'       => $state['saml:idp:NameID'],
            'saml:SessionIndex' => $assertion->getSessionIndex(),
        ];

        $this->idp->addAssociation($association);
    }

    protected static function encryptAssertion(
        Configuration $idpMetadata,
        Configuration $spMetadata,
        Assertion $assertion
    ) {
        $encryptAssertion = $spMetadata->getOptionalBoolean('assertion.encryption', null);
        if ($encryptAssertion === null) {
            $encryptAssertion = $idpMetadata->getOptionalBoolean('assertion.encryption', false);
        }
        if (!$encryptAssertion) {
            // we are _not_ encrypting this assertion, and are therefore done
            return $assertion;
        }


        $sharedKey = $spMetadata->getOptionalString('sharedkey', null);
        if ($sharedKey !== null) {
            $algo = $spMetadata->getOptionalString('sharedkey_algorithm', null);
            if ($algo === null) {
                // If no algorithm is configured, use a sane default
                $algo = $idpMetadata->getOptionalString('sharedkey_algorithm', XMLSecurityKey::AES128_GCM);
            }

            $key = new XMLSecurityKey($algo);
            $key->loadKey($sharedKey);
        } else {
            $keys = $spMetadata->getPublicKeys('encryption', true);
            if (!empty($keys)) {
                $key = $keys[0];
                $pemKey = match ($key['type']) {
                    'X509Certificate' => "-----BEGIN CERTIFICATE-----\n" .
                            chunk_split((string) $key['X509Certificate'], 64) .
                            "-----END CERTIFICATE-----\n",
                    default => throw new Error\Exception('Unsupported encryption key type: ' . $key['type']),
                };

                // extract the public key from the certificate for encryption
                $key = new XMLSecurityKey(XMLSecurityKey::RSA_OAEP_MGF1P, ['type' => 'public']);
                $key->loadKey($pemKey);
            } else {
                throw new \SimpleSAML\Error\ConfigurationError(
                    'Missing encryption key for entity `' . $spMetadata->getString('entityid') . '`',
                    $spMetadata->getString('metadata-set') . '.php',
                    null
                );
            }
        }

        $ea = new EncryptedAssertion();
        $ea->setAssertion($assertion, $key);
        return $ea;
    }

    protected function buildResponse(
        Configuration $idpMetadata,
        Configuration $spMetadata,
        string $consumerURL,
        Assertion $assertion,
        ?bool $shouldSignResponse = null
    ): \SAML2\Response {
        $signResponse = $shouldSignResponse ??
            $spMetadata->getOptionalBoolean('saml20.sign.response', null) ??
             $idpMetadata->getOptionalBoolean('saml20.sign.response', true);

        $r = new \SAML2\Response();
        $issuer = new Issuer();
        $issuer->setValue($idpMetadata->getString('entityid'));
        $issuer->setFormat(Constants::NAMEID_ENTITY);
        $r->setIssuer($issuer);
        $r->setDestination($consumerURL);

        if ($signResponse) {
            \SimpleSAML\Module\saml\Message::addSign($idpMetadata, $spMetadata, $r);
        }

        $r->setInResponseTo($this->requestId);
        $r->setRelayState($this->relayState);
        $r->setAssertions([$assertion]);

        return $r;
    }

    protected function doStats(array $state): void
    {
        $statsData = [
            'spEntityID'  => $this->spEntityId,
            'idpEntityID' => $this->idpMetadata->getString('entityid'),
            'protocol'    => 'saml2',
        ];
        if (isset($state['saml:AuthnRequestReceivedAt'])) {
            $statsData['logintime'] = microtime(true) - $state['saml:AuthnRequestReceivedAt'];
        }
        Stats::log('saml:idp:Response', $statsData);
    }

    protected function sendResponseUsingBinding(\SAML2\Response $response): void
    {
        $binding = Binding::getBinding($this->protocolBinding);
        $binding->send($response);
    }
}
