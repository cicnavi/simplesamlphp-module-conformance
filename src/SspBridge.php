<?php

namespace SimpleSAML\Module\conformance;

use SimpleSAML\Module\conformance\SspBridge\Auth;
use SimpleSAML\Module\conformance\SspBridge\Module;
use SimpleSAML\Module\conformance\SspBridge\Utils;

class SspBridge
{
    protected static ?Utils $utils = null;
    protected static ?Module $module = null;
    protected static ?Auth $auth = null;

    public function utils(): Utils
    {
        return self::$utils ??= new Utils();
    }

    public function module(): Module
    {
        return self::$module ??= new Module();
    }

    public function auth(): Auth
    {
        return self::$auth ??= new Auth();
    }
}
