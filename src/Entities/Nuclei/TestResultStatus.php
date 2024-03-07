<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance\Entities\Nuclei;

use SimpleSAML\Module\conformance\NucleiEnv;

/**
 * @psalm-suppress PossiblyUnusedProperty
 */
class TestResultStatus
{
    public function __construct(
        public readonly string $spEntityId,
        public readonly int $timestamp,
        public readonly ?array $parsedJsonResult = null,
        public readonly ?string $findings = null,
    ) {
    }

    public function isOk(): bool
    {
        if (!is_array($this->parsedJsonResult)) {
            return false;
        }

        // The result is not empty, so there were some findings, meaning something was not right.
        if (!empty($this->parsedJsonResult)) {
            return false;
        }

        // Result is empty array, meaning there were no findings, so the status is ok.
        return true;
    }

    public function getDescription(): string
    {
        if (!is_array($this->parsedJsonResult)) {
            return 'No JSON result available.';
        }

        if (empty($this->parsedJsonResult)) {
            return 'Passed without findings.';
        }

        $extractedResults = [];
        /** @var array $item */
        foreach ($this->parsedJsonResult as $item) {
            if (isset($item['extracted-results']) && is_array($item['extracted-results'])) {
                $extractedResults = array_unique(array_merge($extractedResults, $item['extracted-results']));
            }
        }

        // Make sure we only have strings as values
        array_walk($extractedResults, function (mixed &$value) {
            $value = (string)$value;
        });

        /** @var string[] $extractedResults */
        return 'Found issues related to tests: ' . implode(', ', $extractedResults);
    }
}
