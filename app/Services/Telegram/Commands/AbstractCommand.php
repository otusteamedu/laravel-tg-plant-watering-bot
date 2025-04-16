<?php declare(strict_types = 1);

namespace App\Services\Telegram\Commands;

use App\Services\Telegram\Events\CommandProcessed;
use App\Services\Telegram\Events\CommandReceived;
use App\Services\Telegram\Middleware\Auth;
use Telegram\Bot\Commands\Command;
use Illuminate\Contracts\Events;
use Illuminate\Contracts\Bus;

abstract class AbstractCommand extends Command
{
    protected \App\Services\Telegram\Enum\Command $command;

    /**
     * При попытке чтения свойства $name возвращаем значение из $command
     * см. "php property hooks"
     */
    protected string $name {
        get {
            return $this->command->value;
        }
    }

    public function __construct(
        readonly protected Events\Dispatcher $events,
        readonly protected Bus\Dispatcher $bus,
        readonly protected Auth $auth,
    ) {
    }

    /**
     * Базовая обработка любой команды бота
     */
    public function handle(): void
    {
        $startedAt = microtime(true);

        try {
            /**
             * Проверяем ID пользователя — чужих игнорируем
             */
            $this->auth->handle($this->getUpdate());
        } catch (\Throwable) {
            return;
        }

        /**
         * Обрабатываем команду и порождаем событие об успешном завершении обработки
         */
        $this->process();
        $this->events->dispatch(new CommandProcessed($this->command, microtime(true) - $startedAt));
    }

    protected function process(): void
    {
        $chatId = (int)$this->getUpdate()->getChat()->get('id');
        $messageId = (int)$this->getUpdate()->getMessage()->get('message_id');

        $this->events->dispatch(new CommandReceived($chatId, $messageId, $this->command));
    }
}
