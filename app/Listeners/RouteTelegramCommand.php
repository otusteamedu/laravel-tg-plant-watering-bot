<?php declare(strict_types = 1);

namespace App\Listeners;

use App\DTO\TelegramMessageData;
use App\Services\CommandBus\ContextManager;
use App\Services\Metrics\Enum\Metric;
use App\Services\Metrics\Jobs\FetchMetric;
use App\Services\PlantRPC\DTO\RPCRequest;
use App\Services\PlantRPC\DTO\RunPumpRequest;
use App\Services\PlantRPC\Enum\RPCMethod;
use App\Services\PlantRPC\Jobs\PerformCall;
use App\Services\Telegram\Enum\Command;
use App\Services\Telegram\Enum\MessageReactionEmoji;
use App\Services\Telegram\Events\CommandReceived;
use App\Services\Telegram\Events\RunPumpCommandReceived;
use App\Services\Telegram\Jobs\SendMessage;
use App\Services\Telegram\Jobs\SendReaction;
use Illuminate\Contracts\Bus\Dispatcher;

readonly class RouteTelegramCommand
{
    public function __construct(
        private ContextManager $manager,
        private Dispatcher $bus,
    ) {
    }

    public function __invoke(CommandReceived|RunPumpCommandReceived $event): void
    {
        $this->bus->dispatch(new SendReaction(
            $event->chatId,
            $event->messageId,
            MessageReactionEmoji::EYES,
        ));

        $ctx = $this->manager->createContext(
            new TelegramMessageData($event->chatId, $event->messageId)
        );

        if ($event instanceof RunPumpCommandReceived) {
            $this->bus->dispatch(new PerformCall(new RunPumpRequest($ctx->id, $event->pumpId, $event->seconds)));
        } elseif ($event->command === Command::TEST) {
            $this->bus->dispatch(new SendMessage($event->chatId, 'Current time is ' . new \DateTime()->format(DATE_ATOM)));
            $this->bus->dispatch(new SendReaction(
                $event->chatId,
                $event->messageId,
                MessageReactionEmoji::THUMB_UP,
            ));
        } elseif (in_array($event->command, [Command::GET_TEMP, Command::GET_HUM])) {
            $metric = match ($event->command) {
                Command::GET_TEMP => Metric::AMBIENT_TEMPERATURE,
                Command::GET_HUM => Metric::AMBIENT_HUMIDITY,
                default => null,
            };

            if (null !== $metric) {
                $this->bus->dispatch(new FetchMetric($ctx->id, $metric));
            }
        }
    }
}
