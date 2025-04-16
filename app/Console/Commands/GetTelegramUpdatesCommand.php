<?php

namespace App\Console\Commands;

use App\Services\Telegram\Bot;
use Illuminate\Console\Command;

class GetTelegramUpdatesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:get-telegram-updates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(Bot $bot): int
    {
        $interrupt = false;
        $this->trap([SIGTERM, SIGINT], static function () use (&$interrupt): void { $interrupt = true; });

        while (!$interrupt) {
            try {
                $bot->handleCommand(false);
                sleep(1);
            } catch (\Throwable $e) {
                $this->error($e->getMessage());
            }
        }

        return 0;
    }
}
