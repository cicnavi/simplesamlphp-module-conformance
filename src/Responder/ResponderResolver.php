<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance\Responder;

use SimpleSAML\Module\conformance\Errors\ConformanceException;

class ResponderResolver
{
    public function __construct(
        protected readonly ResponderInterface $responder = new TestResponder()
    ) {
    }

    /**
     * @throws ConformanceException
     */
    public function fromTestId(string $testId): ?callable
    {
        $method = match ($testId) {
            '1', 'standardResponse' => 'standardResponse',
            '2', 'noSignature' => 'noSignature',
            '3', 'invalidSignature' => 'invalidSignature',
            default => null,
        };

        if ($method && method_exists($this->responder, $method)) {
            return [$this->responder, $method];
        }

        return null;
    }

    /**
     * @throws ConformanceException
     */
    public function fromTestIdOrThrow(string $testId): callable
    {
        return $this->fromTestId($testId) ?? throw new ConformanceException(
            "Could not resolve responder for test ID $testId."
        );
    }
}
