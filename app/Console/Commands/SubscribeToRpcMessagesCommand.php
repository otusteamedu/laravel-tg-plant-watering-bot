<?php

namespace App\Console\Commands;

use App\Services\PlantRPC\PlantRPC;
use Illuminate\Console\Command;

class SubscribeToRpcMessagesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:subscribe-to-rpc-messages';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(PlantRPC $plantRPC): int
    {
        // останавливаем обработку ответов RPC при получении сигнала от ОС, например если нажали Ctrl+C
        $this->trap([SIGTERM, SIGINT], static fn() => $plantRPC->stopListeningForResponses());

        try {
            $plantRPC->listenForResponses();
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return 1;
        }

        return 0;
    }
}
