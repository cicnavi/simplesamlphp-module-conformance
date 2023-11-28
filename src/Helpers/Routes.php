<?php

declare(strict_types=1);

namespace SimpleSAML\Module\conformance\Helpers;

use SimpleSAML\Error\CriticalConfigurationError;
use SimpleSAML\Error\Exception;
use SimpleSAML\Module\conformance\ModuleConfig;
use SimpleSAML\Utils\HTTP;

use function sprintf;

class Routes
{
    public const PATH_METADATA_ADD = 'metadata/add';

    protected HTTP $sspHttpUtils;
    protected Arr $arr;

    public function __construct(HTTP $sspHttpUtils = null, Arr $arr = null)
    {
        $this->sspHttpUtils = $sspHttpUtils ?? new HTTP();
        $this->arr = $arr ?? new Arr();
    }

    /**
     * @throws Exception
     */
    public function getUrl(string $path, array $queryParameters = [], array $fragmentParameters = []): string
    {
        try {
            $url = $this->sspHttpUtils->getBaseURL() . 'module.php/' . ModuleConfig::MODULE_NAME . '/' . $path;
            // @codeCoverageIgnoreStart
            // SSP dumps some exception context data when simulating exception, so will ignore coverage for this...
        } catch (CriticalConfigurationError $exception) {
            $message = sprintf('Could not load SimpleSAMLphp base URL. Error was: %s', $exception->getMessage());
            throw new Exception($message, $exception->getCode(), $exception);
            // @codeCoverageIgnoreEnd
        }

        if (!empty($queryParameters)) {
            $url = $this->sspHttpUtils->addURLParameters($url, $queryParameters);
        }

        // Let's assume there are no current fragments in the URL. If the fragment array is not associative,
        // simply append value(s). Otherwise, create key-value fragment pairs.
        if (!empty($fragmentParameters)) {
            /** @psalm-suppress MixedArgumentTypeCoercion */
            $url .= '#' . implode(
                '&',
                (
                    ! $this->arr->isAssociative($fragmentParameters) ?
                    $fragmentParameters :
                    array_map(
                        fn($key, string $value): string => $key . '=' . $value,
                        array_keys($fragmentParameters),
                        $fragmentParameters
                    )
                )
            );
        }

        return $url;
    }
}
