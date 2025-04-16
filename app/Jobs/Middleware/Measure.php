<?php declare(strict_types = 1);

namespace App\Jobs\Middleware;

use App\Services\Metrics\Enum\Metric;
use App\Services\Metrics\Jobs\PushMetric;
use Illuminate\Contracts\Bus\Dispatcher;

/**
 * Middleware для замера времени выполнения фоновых задач (Jobs)
 */
readonly class Measure
{
    public function __construct(
        private Dispatcher $bus,
    ) {
    }

    public function handle(object $job, mixed $next): void
    {
        $startedAt = microtime(true);
        $next($job);
        $this->bus->dispatch(new PushMetric(
            Metric::JOB_EXECUTION_TIME,
            microtime(true) - $startedAt,
            ['job' => $job::class]
        ));
    }
}
