<?php declare(strict_types=1);

namespace App\Listeners;

use App\DTO\TelegramMessageData;
use App\Services\CommandBus\ContextManager;
use App\Services\PlantRPC\DTO\GetHumResponse;
use App\Services\PlantRPC\DTO\GetTempResponse;
use App\Services\PlantRPC\Events\ResponseReceived;
use App\Services\Telegram\Enum\MessageReactionEmoji;
use App\Services\Telegram\Jobs\SendMessage;
use App\Services\Telegram\Jobs\SendReaction;
use Illuminate\Contracts\Bus\Dispatcher;

readonly class NotifyOnCompletedRPC
{
    public function __construct(
        private ContextManager $manager,
        private Dispatcher $bus,
    ) {
    }

    public function __invoke(ResponseReceived $event): void
    {
        $ctx = $this->manager->getContext($event->response->id);
        if (null !== $ctx) {
            if ($ctx->payload instanceof TelegramMessageData) {
                /**
                 * Ставим сообщению палец вверх или вниз в зависимости от того, каков результат выполнения запроса к PlantRPC
                 */
                $this->bus->dispatch(new SendReaction(
                    $ctx->payload->chatId,
                    $ctx->payload->messageId,
                    $event->response->ok ? MessageReactionEmoji::THUMB_UP : MessageReactionEmoji::THUMB_DOWN,
                ));

                if ($event->response instanceof GetTempResponse) {
                    /**
                     * Отправляем сообщение с температурой
                     */
                    $this->replyWith(
                        $ctx->payload,
                        $event->response->ok
                            ? sprintf('%0.1f ºC', $event->response->value)
                            : ($event->response->msg ?? 'Something went wrong :('),
                    );
                } elseif ($event->response instanceof GetHumResponse) {
                    /**
                     * Отправляем сообщение с влажностью
                     */
                    $this->replyWith(
                        $ctx->payload,
                        $event->response->ok
                            ? sprintf('%0.1f %%', $event->response->value)
                            : ($event->response->msg ?? 'Something went wrong :('),
                    );
                }
            }

            $this->manager->clearContext($ctx);
        }
    }

    private function replyWith(TelegramMessageData $messageData, string $text): void
    {
        $this->bus->dispatch(new SendMessage(
            $messageData->chatId,
            $text,
        ));
    }
}
