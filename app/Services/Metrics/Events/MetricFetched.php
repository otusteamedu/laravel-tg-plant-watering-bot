<?php declare(strict_types=1);

namespace App\Services\Metrics\Events;

use App\Services\Metrics\Enum\Metric;

readonly class MetricFetched
{
    public function __construct(
        public string $id,
        public Metric $metric,
        public int|float|null $value,
    ) {
    }
}
