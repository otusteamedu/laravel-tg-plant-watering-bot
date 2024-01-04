<?php declare(strict_types = 1);

namespace App\Services\Telegram\Events;

use App\Services\Telegram\Enum\Command;

readonly class CommandReceived
{
    public function __construct(
        public int $chatId,
        public int $messageId,
        public Command $command,
    ) {
    }
}
