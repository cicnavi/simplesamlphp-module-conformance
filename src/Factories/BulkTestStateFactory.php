<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance\Factories;

use SimpleSAML\Module\conformance\BulkTest\State;

class BulkTestStateFactory
{
    public function build(int $runnerId): State
    {
        return new State($runnerId);
    }
}