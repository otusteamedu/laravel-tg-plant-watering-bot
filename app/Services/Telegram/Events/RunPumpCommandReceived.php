<?php declare(strict_types = 1);

namespace App\Services\Telegram\Events;

use App\Services\Telegram\Enum\Command;

readonly class RunPumpCommandReceived extends CommandReceived
{
    public function __construct(
        int $chatId,
        int $messageId,
        public int $pumpId,
        public int $seconds,
    ) {
        parent::__construct($chatId, $messageId, Command::RUN_PUMP);
    }
}
