<?php declare(strict_types = 1);

namespace App\Listeners;

use App\Services\Metrics\Enum\Metric;
use App\Services\Metrics\Jobs\PushMetric;
use App\Services\Telegram\Events\CommandProcessed;
use Illuminate\Contracts\Bus\Dispatcher;

readonly class PushTelegramCommandProcessingTimeMetric
{
    public function __construct(
        private Dispatcher $bus,
    ) {
    }

    public function __invoke(CommandProcessed $event): void
    {
        /**
         * Сохраняем время обработки команды из Telegram в InfluxDB посредством фоновой задачи PushMetric
         */
        $this->bus->dispatch(new PushMetric(
            Metric::BOT_COMMAND_PROCESSING_TIME,
            $event->elapsedTime,
            ['command' => $event->command->value],
        ));
    }
}
