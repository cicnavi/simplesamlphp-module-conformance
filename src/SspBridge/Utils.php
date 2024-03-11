<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance\SspBridge;

use SimpleSAML\Utils\EMail;
use SimpleSAML\Utils\HTTP;
use SimpleSAML\Utils\Auth;
use SimpleSAML\Utils\XML;

class Utils
{
    protected static ?HTTP $http = null;
    protected static ?Auth $auth = null;
    protected static ?XML $xml = null;

    public function http(): HTTP
    {
        return self::$http ??= new HTTP();
    }

    public function auth(): Auth
    {
        return self::$auth ??= new Auth();
    }

    public function xml(): XML
    {
        return self::$xml ??= new XML();
    }
}
