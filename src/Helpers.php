<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance;

use SimpleSAML\Module\conformance\Helpers\Arr;
use SimpleSAML\Module\conformance\Helpers\Database;
use SimpleSAML\Module\conformance\Helpers\Filesystem;
use SimpleSAML\Module\conformance\Helpers\Routes;
use SimpleSAML\Module\conformance\Helpers\Shell;
use SimpleSAML\Module\conformance\Helpers\State;
use SimpleSAML\Module\conformance\Helpers\Random;

class Helpers
{
    protected static ?Arr $arr = null;
    protected static ?Filesystem $filesystem = null;
    protected static ?Routes $routes = null;
    protected static ?State $state = null;

    protected static ?Database $database = null;
    protected static ?Shell $shell = null;
    protected static ?Random $random = null;

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

    public function database(): Database
    {
        return self::$database ??= new Database();
    }

    public function shell(): Shell
    {
        return self::$shell ??= new Shell();
    }

    public function random(): Random
    {
        return self::$random ??= new Random();
    }
}
