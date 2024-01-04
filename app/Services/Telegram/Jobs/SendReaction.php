<?php declare(strict_types = 1);

namespace App\Services\Telegram\Jobs;

use App\Jobs\Traits\Measurable;
use App\Services\Telegram\Bot;
use App\Services\Telegram\Enum\MessageReactionEmoji;
use Illuminate\Contracts\Queue\ShouldQueue;

readonly class SendReaction implements ShouldQueue
{
    use Measurable;

    public function __construct(
        private int $chatId,
        private int $messageId,
        private MessageReactionEmoji $emoji,
    ) {
    }

    public function handle(Bot $bot): void
    {
        $bot->sendReaction($this->chatId, $this->messageId, $this->emoji);
    }
}
