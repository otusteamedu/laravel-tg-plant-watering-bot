<?php declare(strict_types=1);

namespace App\Listeners;

use App\Services\Metrics\Enum\Metric;
use App\Services\Metrics\Jobs\PushMetric;
use App\Services\PlantRPC\DTO\GetHumResponse;
use App\Services\PlantRPC\DTO\GetTempResponse;
use App\Services\PlantRPC\Events\ResponseReceived;
use Illuminate\Contracts\Bus\Dispatcher;

readonly class PushAmbienceMetric
{
    public function __construct(
        private Dispatcher $bus,
    ) {
    }

    public function __invoke(ResponseReceived $event): void
    {
        if ($event->response instanceof GetTempResponse) {
            $this->bus->dispatch(new PushMetric(
                Metric::AMBIENT_TEMPERATURE,
                $event->response->value,
            ));
        } elseif ($event->response instanceof GetHumResponse) {
            $this->bus->dispatch(new PushMetric(
                Metric::AMBIENT_HUMIDITY,
                $event->response->value,
            ));
        }
    }
}
