<?php declare(strict_types=1);

namespace App\Listeners;

use App\DTO\TelegramMessageData;
use App\Services\CommandBus\ContextManager;
use App\Services\Metrics\Enum\Metric;
use App\Services\Metrics\Events\MetricFetched;
use App\Services\PlantRPC\DTO\RPCRequest;
use App\Services\PlantRPC\Enum\RPCMethod;
use App\Services\PlantRPC\Jobs\PerformCall;
use App\Services\Telegram\Jobs\SendMessage;
use Illuminate\Contracts\Bus\Dispatcher;

readonly class SendFetchedMetricValueOrUpdate
{
    public function __construct(
        private ContextManager $contextManager,
        private Dispatcher $bus,
    ) {
    }

    public function __invoke(MetricFetched $event): void
    {
        $ctx = $this->contextManager->getContext($event->id);
        if (null !== $ctx && $ctx->payload instanceof TelegramMessageData) {
            if (null !== $event->value) {
                /**
                 * Если удалось достать свежее значение метрики из InfluxDB, отправляем соответствующее сообщение
                 */
                $this->bus->dispatch(new SendMessage(
                    $ctx->payload->chatId,
                    sprintf(
                        '%0.1f %s',
                        $event->value,
                        match ($event->metric) {
                            Metric::AMBIENT_TEMPERATURE => 'ºC',
                            Metric::AMBIENT_HUMIDITY => '%',
                            default => '',
                        }
                    ),
                ));
            } else {
                /**
                 * Иначе — забираем значение из PlantRPC
                 */
                $method = match ($event->metric) {
                    Metric::AMBIENT_TEMPERATURE => RPCMethod::GET_TEMP,
                    Metric::AMBIENT_HUMIDITY => RPCMethod::GET_HUM,
                    default => null,
                };
                if (null !== $method) {
                    $this->bus->dispatch(new PerformCall(
                        new RPCRequest(
                            $ctx->id,
                            $method
                        ),
                    ));
                }
            }
        }
    }
}
