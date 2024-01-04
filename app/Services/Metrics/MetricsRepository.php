<?php declare(strict_types=1);

namespace App\Services\Metrics;

use App\Services\Metrics\Enum\Metric;
use InfluxDB2\Client;
use InfluxDB2\Point;

readonly class MetricsRepository
{
    private const string DEFAULT_QUERY_RANGE_START = '-1m';
    private const string DEFAULT_FIELD_NAME = 'value';

    public function __construct(
        private Client $client,
        private string $defaultBucket,
    ) {
    }

    public function readLastNumericValue(Metric $metric): int|float|null
    {
        $data = $this->client
            ->createQueryApi()
            ->queryStream(
                sprintf(
                    <<<'FLUX'
                    from(bucket: "%s")
                      |> range(start: %s)
                      |> filter(fn: (r) =>
                        r._measurement == "%s" and
                        r._field == "%s"
                      )
                      |> last()
                    FLUX,
                    $this->defaultBucket,
                    self::DEFAULT_QUERY_RANGE_START,
                    $metric->value,
                    self::DEFAULT_FIELD_NAME,
                )
            );

        if (null !== $data) {
            foreach ($data->each() as $record) {
                return $record->getValue();
            }
        }

        return null;
    }

    public function writeNumericValue(Metric $metric, int|float $value, array $tags = [], ?float $time = null): void
    {
        $point = Point::measurement($metric->value);
        $point->addField(self::DEFAULT_FIELD_NAME, $value);
        $point->time($time ?? microtime(true));
        if (!empty($tags)) {
            array_map(
                static fn(mixed $value, string $key) => $point->addTag($key, $value),
                array_values($tags),
                array_keys($tags),
            );
        }

        $this->client
            ->createWriteApi()
            ->write($point);
    }
}
