<?php declare(strict_types = 1);

namespace App\Services\Metrics\Jobs;

use App\Jobs\Traits\Measurable;
use App\Services\Metrics\Enum\Metric;
use App\Services\Metrics\MetricsRepository;
use Illuminate\Contracts\Queue\ShouldQueue;

readonly class PushMetric implements ShouldQueue
{
    /**
     * @param array<string,mixed> $tags
     */
    public function __construct(
        private Metric $metric,
        private int|float $value,
        private array $tags = [],
        private ?int $time = null,
    ) {
    }

    public function handle(MetricsRepository $repository): void
    {
        $repository->writeNumericValue($this->metric, $this->value, $this->tags, $this->time ?? microtime(true));
    }
}
