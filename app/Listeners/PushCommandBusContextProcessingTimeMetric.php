<?php declare(strict_types = 1);

namespace App\Listeners;

use App\Services\CommandBus\Events\ContextCompleted;
use App\Services\Metrics\Enum\Metric;
use App\Services\Metrics\Jobs\PushMetric;
use Illuminate\Contracts\Bus\Dispatcher;

readonly class PushCommandBusContextProcessingTimeMetric
{
    public function __construct(
        private Dispatcher $bus,
    ) {
    }

    public function __invoke(ContextCompleted $event): void
    {
        /**
         * Сохраняем время обработки контекста команды в InfluxDB посредством фоновой задачи PushMetric
         */
        $this->bus->dispatch(new PushMetric(
            Metric::COMMAND_CONTEXT_PROCESSING_TIME,
            $event->completedAt - $event->ctx->createdAt,
        ));
    }
}
