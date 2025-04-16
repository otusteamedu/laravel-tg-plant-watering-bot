<?php declare(strict_types = 1);

namespace App\Services\Telegram;

use App\Services\Telegram\Enum\MessageReactionEmoji;
use Telegram\Bot\Api;

readonly class Bot
{
    public function __construct(
        private Api $api,
    ) {
    }

    public function handleCommand(bool $webhook = true): void
    {
        $this->api->commandsHandler($webhook);
    }

    /**
     * Ставим реакцию на сообщение
     */
    public function sendReaction(int $chatId, int $messageId, MessageReactionEmoji $emoji): void
    {
        $this->api->setMessageReaction([
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'reaction' => [[
                'type' => 'emoji',
                'emoji' => $emoji->value,
            ]],
        ]);
    }

    /**
     * Отправляем сообщение в чат с пользователем
     */
    public function sendMessage(int $chatId, string $text): void
    {
        $this->api->sendMessage([
            'chat_id' => $chatId,
            'text' => $text,
        ]);
    }
}
