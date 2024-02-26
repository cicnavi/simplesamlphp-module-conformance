<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance;

use SimpleSAML\Module\conformance\Helpers\Arr;
use SimpleSAML\Module\conformance\Helpers\Filesystem;
use SimpleSAML\Module\conformance\Helpers\Routes;
use SimpleSAML\Module\conformance\Helpers\State;

class Helpers
{
    protected static ?Arr $arr = null;
    protected static ?Filesystem $filesystem = null;
    protected static ?Routes $routes = null;
    protected static ?State $state = null;

    public function arr(): Arr
    {
        return self::$arr ??= new Arr();
    }
    public function filesystem(): Filesystem
    {
        return self::$filesystem ??= new Filesystem();
    }

    public function routes(): Routes
    {
        return self::$routes ??= new Routes();
    }

    public function state(): State
    {
        return self::$state ??= new State();
    }
}
