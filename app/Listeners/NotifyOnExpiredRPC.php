<?php declare(strict_types=1);

namespace App\Listeners;

use App\DTO\TelegramMessageData;
use App\Services\CommandBus\ContextManager;
use App\Services\PlantRPC\Events\CallExpired;
use App\Services\Telegram\Enum\MessageReactionEmoji;
use App\Services\Telegram\Jobs\SendReaction;
use Illuminate\Contracts\Bus\Dispatcher;

readonly class NotifyOnExpiredRPC
{
    public function __construct(
        private ContextManager $manager,
        private Dispatcher $bus,
    ) {
    }

    public function __invoke(CallExpired $event): void
    {
        $ctx = $this->manager->getContext($event->id);
        if (null !== $ctx) {
            if ($ctx->payload instanceof TelegramMessageData) {
                $this->bus->dispatch(new SendReaction(
                    $ctx->payload->chatId,
                    $ctx->payload->messageId,
                    MessageReactionEmoji::MOON_DARK,
                ));
            }
            $this->manager->clearContext($ctx);
        }
    }
}
