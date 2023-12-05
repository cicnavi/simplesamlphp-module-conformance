<?php

namespace SimpleSAML\Module\conformance\SspBridge;

use SimpleSAML\Utils\HTTP;

class Utils
{
    protected static ?HTTP $http = null;

    public function http(): HTTP
    {
        return self::$http ??= new HTTP();
    }
}
