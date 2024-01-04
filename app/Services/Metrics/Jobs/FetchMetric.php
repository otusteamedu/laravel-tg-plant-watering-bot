<?php declare(strict_types=1);

namespace App\Services\Metrics\Jobs;

use App\Jobs\Traits\Measurable;
use App\Services\Metrics\Enum\Metric;
use App\Services\Metrics\Events\MetricFetched;
use App\Services\Metrics\MetricsRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Events\Dispatcher;

readonly class FetchMetric implements ShouldQueue
{
    use Measurable;

    public function __construct(
        private string $id,
        private Metric $metric,
    ) {
    }

    public function handle(
        MetricsRepository $repository,
        Dispatcher $events,
    ): void {
        $events->dispatch(new MetricFetched(
            $this->id,
            $this->metric,
            $repository->readLastNumericValue($this->metric),
        ));
    }
}
