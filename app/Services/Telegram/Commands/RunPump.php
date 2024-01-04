<?php declare(strict_types = 1);

namespace App\Services\Telegram\Commands;

use App\Services\Telegram\Events\RunPumpCommandReceived;
use App\Services\Telegram\Enum;

class RunPump extends AbstractCommand
{
    public const int DEFAULT_PUMP = 0;
    public const int DEFAULT_SECONDS = 1;

    protected Enum\Command $command = Enum\Command::RUN_PUMP;
    protected string $pattern = '{pump: \d} {seconds: \d}';
    protected string $description = 'Run pump';

    public function process(): void
    {
        $pump = (int)$this->argument('pump', self::DEFAULT_PUMP);
        $seconds = (int)$this->argument('seconds', self::DEFAULT_SECONDS);

        $chatId = (int)$this->getUpdate()->getChat()->get('id');
        $messageId = (int)$this->getUpdate()->getMessage()->get('message_id');

        $this->events->dispatch(new RunPumpCommandReceived($chatId, $messageId, $pump, $seconds));
    }
}
