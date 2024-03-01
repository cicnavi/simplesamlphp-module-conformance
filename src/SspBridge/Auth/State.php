<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance\SspBridge\Auth;

use SimpleSAML\Module\conformance\Errors\ConformanceException;
use Throwable;

class State
{
    public function saveState(array &$state, string $stage, bool $rawId = false): string
    {
        return \SimpleSAML\Auth\State::saveState($state, $stage, $rawId);
    }

    /**
     * @throws ConformanceException
     */
    public function loadState(string $id, string $stage): array
    {
        try {
            return \SimpleSAML\Auth\State::loadState($id, $stage);
        } catch (Throwable $e) {
            throw new ConformanceException('Unable to load SimpleSAMLphp state.', (int)$e->getCode(), $e);
        }
    }
}
