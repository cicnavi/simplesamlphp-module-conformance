[![Test](https://github.com/cicnavi/simplesamlphp-module-conformance/actions/workflows/test.yml/badge.svg)](https://github.com/cicnavi/simplesamlphp-module-conformance/actions/workflows/test.yml)

# simplesamlphp-module-conformance
SimpleSAMLphp module provides conformance functionality using SimpleSAMLphp authentication processing filters feature.

## Features
- 

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

## Adding Authentication Processing Filter
Last step to start tracking user data using the configured tracker classes / jobs store is to add an [authentication
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

## Tests
To run phpcs, psalm and phpunit:

```shell
composer pre-commit
```