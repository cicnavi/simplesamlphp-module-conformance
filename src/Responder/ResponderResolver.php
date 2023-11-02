<?php

namespace SimpleSAML\Module\conformance\Responder;

class ResponderResolver
{
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