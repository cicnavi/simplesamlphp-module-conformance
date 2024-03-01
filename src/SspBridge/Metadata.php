<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance\SspBridge;

use SimpleSAML\Module\conformance\SspBridge\Metadata\SAMLParser;

class Metadata
{
    protected static ?SAMLParser $samlParser = null;

    public function samlParser(): SAMLParser
    {
        return self::$samlParser ??= new SAMLParser();
    }
}
