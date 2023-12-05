<?php

namespace SimpleSAML\Module\conformance\SspBridge\Auth;

class State
{
    public function saveState(array &$state, string $stage, bool $rawId = false): string
    {
        return \SimpleSAML\Auth\State::saveState($state, $stage, $rawId);
    }
}
