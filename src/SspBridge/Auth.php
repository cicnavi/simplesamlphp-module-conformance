<?php

namespace SimpleSAML\Module\conformance\SspBridge;

use SimpleSAML\Module\conformance\SspBridge\Auth\State;

class Auth
{
    protected static ?State $state = null;

    public function state(): State
    {
        return self::$state ??= new State();
    }
}
