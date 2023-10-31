<?php

namespace SimpleSAML\Module\conformance\Responder;

use SimpleSAML\Module\conformance\Responder\TestResponder;

class ResponderResolver
{
	public const DEFAULT_RESPONDER_CLASS = TestResponder::class;

	public function fromTestId(string $testId, ResponderInterface $responder = null): ?array
	{
		$responder = $responder ?? new TestResponder();

		$method = match ($testId) {
			'1', 'standardResponse' => 'standardResponse',
			'2', 'noSignature' => 'noSignature',
			'3', 'invalidSignature' => 'invalidSignature',
			default => null,
		};

		if ($method && method_exists($responder, $method)) {
			return [$responder, $method];
		}

		return null;
	}
}