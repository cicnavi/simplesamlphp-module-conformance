[![Test](https://github.com/cicnavi/simplesamlphp-module-conformance/actions/workflows/test.yml/badge.svg)](https://github.com/cicnavi/simplesamlphp-module-conformance/actions/workflows/test.yml)

# simplesamlphp-module-conformance

SimpleSAMLphp module provides conformance functionality using SimpleSAMLphp authentication processing filters feature.

## Features

- authentication processing filter that can modify SAML Responses, that is, create invalid ones in order to test SP behavior
- ability to run Nuclei tests from the module UI
- API which enables programmatic control and execution of tests

## Installation

Module requires SimpleSAMLphp version 2 or higher.

Module is installable using Composer:

```shell
composer require cicnavi/simplesamlphp-module-conformance
```

In config.php, search for the "module.enable" key and set 'conformance' to true:

```php
// ...
'module.enable' => [
    'conformance' => true,
    // ...
],
// ...
```

## Configuration

As usual with SimpleSAMLphp modules, copy the module template configuration to the SimpleSAMLphp config directory:

```shell
cp modules/conformance/config-templates/module_conformance.php config/
```

Next step is to configure available options in file config/module_conformance.php. Each option has an explanation,
however, the description of the overall concept follows.

### Database connection

This module needs a database connection to be set in config/config.php. Once the connection is set, navigate to
SimpleSAMLphp administration area > Configuration > Conformance (Details area) > Module Overview, and run the
available migrations.

### PDO Metadata Storage Handler

This module relies on PDO as being set as a metadata storage handler in SimpleSAMLphp. Please go through the following
documentation to set it up: <https://simplesamlphp.org/docs/stable/simplesamlphp-metadata-pdostoragehandler>

Note that in addition to PDO metadata storage handler, you are free to use any other metadata source supported
by SimpleSAMLphp.

### Nuclei installation

In order to run Nuclei tests from the conformance module UI, the Nuclei has to be installed on the server. In addition,
SimpleSAMLphp (web server) has to be able to run it.

The Nuclei working directory will be the one set in the 'datadir' option in config/config.php. Make sure that
SimpleSAMLphp can write to it. This directory will be used to store Nuclei related data like its internal config and
cache... It will also be used to store any test result artifacts like JSON reports, screenshots, etc.

### Sending emails

Conformance module can be set to ask Service Provider contacts for consent before running tests on them. It will do that
by emailing them a link which can be used to accept testing. In order to be able to send emails, make sure to set
appropriate email related options in config/config.php (and that you have appropriate software available like
sendmail / postfix / smtp, depending on your configuration...).

### Adding Authentication Processing Filter

In order to be able to alter SAML responses, it is necessary to add an [authentication
processing filter](https://simplesamlphp.org/docs/stable/simplesamlphp-authproc.html) from the conformance module
to the right place in SimpleSAMLphp configuration. Here is an example of setting it globally for all IdPs
in config/config.php:

```php
// ...
'authproc.idp' => [
        // ... 
        1000 => 'conformance:Conformance',
    ],
// ...
```

## API

API endpoints are protected with Authorization Bearer token. Available access tokens can be preset in
config/module_conformance.php. Tokens can be 'administrative' (can be used to run tests on any SP), or can be intended
for only particular service providers.

In order to access the API, you must provide the token in the HTTP request as the Authorization header, with Bearer
scheme. For example:

```plain
GET /resource HTTP/1.1
Host: server.example.com
Authorization: Bearer sometoken

...
```

Available endpoints are described below.

### Test modification

Endpoint to define next test for particular SP.

URI: `https://conformance-idp.example.com/module.php/conformance/test/setup`

HTTP method: GET

Parameters:

- testId
  - valid values:
    - `standardResponse`: doesn't modify SAML responses (SAML responses are signed with regular private key)
    - `noSignature`: SAML responses don't include signature
    - `invalidSignature`: SAML responses are signed with wrong private key
  - example: `noSignature`
- spEntityId
  - valid values: any trusted SP Entity ID
  - example: `urn:x-simplesamlphp:geant:incubator:simplesamlphp-sp:good-sp`

For example, to specify that the next test for the SP `urn:x-simplesamlphp:geant:incubator:simplesamlphp-sp:good-sp`
should be the one that doesn't sign the SAML Response, make a HTTP GET request to:

`https://conformance-idp.example.com/module.php/conformance/test/setup?testId=noSignature&spEntityId=urn:x-simplesamlphp:geant:incubator:simplesamlphp-sp:good-sp`

### SP metadata provisioning

Endpoint to provision SP metadata which will be trusted by the Conformance IdP. This feature will use PDO metadata
store to persist metadata.

URI: `https://conformance-idp.example.com/module.php/conformance/metadata/persist`

HTTP method: POST

Parameters:

- xmlData - optional (mandatory if xmlFile not provided)
  - valid values: SAML2 SP metadata XML string
  - example:

    ```xml
    <?xml version="1.0" encoding="utf-8"?>
    <md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata" entityID="urn:x-simplesamlphp:geant:incubator:simplesamlphp-sp:good-sp">
    <md:SPSSODescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">
    <md:SingleLogoutService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect" Location="https://simplesamlphp-sp.maiv1.incubator.geant.org/simplesaml/module.php/saml/sp/saml2-logout.php/good-sp"/>
    <md:AssertionConsumerService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" Location="https://simplesamlphp-sp.maiv1.incubator.geant.org/simplesaml/module.php/saml/sp/saml2-acs.php/good-sp" index="0"/>
    <md:AssertionConsumerService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Artifact" Location="https://simplesamlphp-sp.maiv1.incubator.geant.org/simplesaml/module.php/saml/sp/saml2-acs.php/good-sp" index="1"/>
    </md:SPSSODescriptor>
    </md:EntityDescriptor>
    ```

- xmlFile - optional (mandatory if xmlData not provided)
  - valid values: SAML2 SP XML metadata file

## IdP Initiated Login

IdP initiated login can be performed as per SimpleSAMLphp documentation: <https://simplesamlphp.org/docs/2.1/simplesamlphp-idp-more.html>

Sample URI to initiate login to SP 'urn:x-simplesamlphp:geant:incubator:simplesamlphp-sp:good-sp':

`https://conformance-idp.example.com/saml2/idp/SSOService.php?spentityid=urn:x-simplesamlphp:geant:incubator:simplesamlphp-sp:good-sp`

## Tests

To run phpcs, psalm and phpunit:

```shell
composer pre-commit
```
