<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance\SspBridge\Metadata;

use Exception;
use SimpleSAML\Module\conformance\Errors\ConformanceException;

class SAMLParser
{
    /**
     * @throws ConformanceException
     * @return \SimpleSAML\Metadata\SAMLParser[]
     */
    public function parseDescriptorsString(string $string): array
    {
        try {
            return \SimpleSAML\Metadata\SAMLParser::parseDescriptorsString($string);
        } catch (Exception $exception) {
            throw new ConformanceException(
                'Error parsing descriptors string.',
                (int)$exception->getCode(),
                $exception
            );
        }
    }
}
