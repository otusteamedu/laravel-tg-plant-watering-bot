<?php declare(strict_types=1);

namespace App\Listeners;

use App\Services\Metrics\Enum\Metric;
use App\Services\Metrics\Jobs\PushMetric;
use App\Services\PlantRPC\Events\ResponseReceived;
use Illuminate\Contracts\Bus\Dispatcher;

readonly class PushRPCResponseTimeMetric
{
    public function __construct(
        private Dispatcher $bus,
    ) {
    }

    public function __invoke(ResponseReceived $event): void
    {
        /**
         * Сохраняем время обработки вызова PlantRPC в InfluxDB посредством фоновой задачи PushMetric
         */
        $this->bus->dispatch(new PushMetric(
            Metric::RPC_RESPONSE_TIME,
            $event->elapsedTime,
            ['method' => $event->method->value],
        ));
    }
}
