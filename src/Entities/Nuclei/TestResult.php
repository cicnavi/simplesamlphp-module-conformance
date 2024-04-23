<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance\Entities\Nuclei;

use JsonException;
use JsonSerializable;
use SimpleSAML\Module\conformance\Errors\ConformanceException;

/**
 * @psalm-suppress PossiblyUnusedProperty
 */
class TestResult implements JsonSerializable
{
    final public const COLUMN_ID = 'id';
    final public const COLUMN_SP_ENTITY_ID = 'sp_entity_id';
    final public const COLUMN_HAPPENED_AT = 'happened_at';
    final public const COLUMN_IS_OK = 'is_ok';
    final public const DESCRIPTION = 'description';

    public readonly ?array $parsedJsonResult;

    /**
     * @throws ConformanceException|JsonException
     */
    public function __construct(
        public readonly int $id,
        public readonly string $spEntityId,
        public readonly int $happenedAt,
        ?string $jsonResult = null,
        public readonly ?string $findings = null,
    ) {
        $this->parsedJsonResult = $this->resolveJsonResult($jsonResult);
    }

    public function isOk(): bool
    {
        if (!is_array($this->parsedJsonResult)) {
            return false;
        }

        // If JSON result is not empty, there were some findings, meaning something was not right.
        if (!empty($this->parsedJsonResult)) {
            return false;
        }

        // JSON result was empty, but check for other errors in findings.
        if (!empty($this->findings)) {
            return false;
        }

        // There were no findings, so the status is ok.
        return true;
    }

    public function getDescription(): string
    {
        if (!is_array($this->parsedJsonResult)) {
            return 'No JSON result available.';
        }

        if (!empty($this->parsedJsonResult)) {
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

        if (!empty($this->findings)) {
            return sprintf("There were some issues: %s", $this->findings);
        }

        return 'Passed without findings.';
    }

    public function jsonSerialize(): array
    {
        return [
            self::COLUMN_ID => $this->id,
            self::COLUMN_SP_ENTITY_ID => $this->spEntityId,
            self::COLUMN_HAPPENED_AT => $this->happenedAt,
            self::COLUMN_IS_OK => $this->isOk(),
            self::DESCRIPTION => $this->getDescription(),
        ];
    }

    /**
     * @throws ConformanceException
     * @throws JsonException
     */
    protected function resolveJsonResult(?string $jsonResult): ?array
    {
        /** @psalm-suppress MixedAssignment */
        $jsonResult = is_null($jsonResult) ?
            null :
            json_decode($jsonResult, true, 512, JSON_THROW_ON_ERROR);

        if (is_null($jsonResult) || is_array($jsonResult)) {
            return $jsonResult;
        }

        throw new ConformanceException('Unexpected JSON Result value');
    }
}
