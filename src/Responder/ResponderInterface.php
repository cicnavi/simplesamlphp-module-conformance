<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance\Responder;

interface ResponderInterface
{
    public function standardResponse(array $state): void;
    public function noSignature(array $state): void;
    public function invalidSignature(array $state): void;
}
