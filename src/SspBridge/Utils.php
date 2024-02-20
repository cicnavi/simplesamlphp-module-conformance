<?php

namespace SimpleSAML\Module\conformance\SspBridge;

use SimpleSAML\Utils\HTTP;
use SimpleSAML\Utils\Auth;

class Utils
{
    protected static ?HTTP $http = null;

    protected static ?Auth $auth = null;

    public function http(): HTTP
    {
        return self::$http ??= new HTTP();
    }

    public function auth(): Auth
    {
        return self::$auth ??= new Auth();
    }
}
