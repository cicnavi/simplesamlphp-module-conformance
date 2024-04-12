<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance;

use SimpleSAML\Module\conformance\SspBridge\Auth;
use SimpleSAML\Module\conformance\SspBridge\Metadata;
use SimpleSAML\Module\conformance\SspBridge\Module;
use SimpleSAML\Module\conformance\SspBridge\Utils;

class SspBridge
{
    final public const KEY_SET_SP_REMOTE = 'saml20-sp-remote';

    protected static ?Utils $utils = null;
    protected static ?Module $module = null;
    protected static ?Auth $auth = null;
    protected static ?Metadata $metadata = null;

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

    public function metadata(): Metadata
    {
        return self::$metadata ??= new Metadata();
    }
}
