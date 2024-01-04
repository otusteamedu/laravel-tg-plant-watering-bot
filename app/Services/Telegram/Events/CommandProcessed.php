<?php declare(strict_types = 1);

namespace App\Services\Telegram\Events;

use App\Services\Telegram\Enum\Command;

readonly class CommandProcessed
{
    public function __construct(
        public Command $command,
        public float $elapsedTime,
    ) {
    }
}
