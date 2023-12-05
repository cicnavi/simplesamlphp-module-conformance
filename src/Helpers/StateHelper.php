<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance\Helpers;

use Exception;
use SimpleSAML\Module\conformance\Errors\ConformanceException;

class StateHelper
{
    /**
     * @throws Exception
     */
    public function resolveSpEntityId(array $state): string
    {
        $spEntityId = $state['Destination']['entityid'] ?? null;

        if (is_null($spEntityId)) {
            throw new ConformanceException('No SP ID.');
        }
        return (string)$spEntityId;
    }
}
