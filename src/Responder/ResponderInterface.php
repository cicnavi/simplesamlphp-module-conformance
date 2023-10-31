<?php

namespace SimpleSAML\Module\conformance\Responder;

use Symfony\Component\HttpFoundation\Response;

interface ResponderInterface
{
	public function standardResponse(array $state): void;
	public function noSignature(array $state): void;
	public function invalidSignature(array $state): void;
}