<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance\Helpers;

use SimpleSAML\Module\conformance\Errors\ConformanceException;

class State
{
    final public const KEY_RESPONDER = 'Responder';

    /**
     * @throws ConformanceException
     */
    public function resolveSpEntityId(array $state): string
    {
        /** @var mixed $spEntityId */
        $spEntityId = $state['Destination']['entityid'] ?? null;

        if (empty($spEntityId)) {
            throw new ConformanceException('No SP entity ID available in state array.');
        }

        return (string)$spEntityId;
    }

    /**
     * @throws ConformanceException
     */
    public function setResponder(array &$state, callable $responder): void
    {
        if (!isset($state[self::KEY_RESPONDER])) {
            throw new ConformanceException(
                'No existing responder set in state which indicates that the conformance authentication ' .
                'processing filter was not set on IdP level.'
            );
        }

        $state[self::KEY_RESPONDER] = $responder;
    }
}
