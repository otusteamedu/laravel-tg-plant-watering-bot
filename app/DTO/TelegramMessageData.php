<?php declare(strict_types=1);

namespace App\DTO;

readonly class TelegramMessageData
{
    public function __construct(
        public int $chatId,
        public int $messageId
    ) {
    }
}
