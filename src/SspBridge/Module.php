<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance\SspBridge;

class Module
{
    public function getModuleURL(string $resource, array $parameters = []): string
    {
        return \SimpleSAML\Module::getModuleURL($resource, $parameters);
    }
}
