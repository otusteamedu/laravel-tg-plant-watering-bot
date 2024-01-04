<?php declare(strict_types = 1);

namespace App\Services\Telegram\Jobs;

use App\Jobs\Traits\Measurable;
use App\Services\Telegram\Bot;
use Illuminate\Contracts\Queue\ShouldQueue;

readonly class SendMessage implements ShouldQueue
{
    use Measurable;

    public function __construct(
        private int $chatId,
        private string $text,
    ) {
    }

    public function handle(Bot $bot): void
    {
        $bot->sendMessage($this->chatId, $this->text);
    }
}
