<?php

return [
    \App\Services\Metrics\MetricsServiceProvider::class,
    \App\Services\CommandBus\CommandBusServiceProvider::class,
    \App\Services\PlantRPC\PlantRPCServiceProvider::class,
    \Telegram\Bot\Laravel\TelegramServiceProvider::class,
    \App\Services\Telegram\TelegramServiceProvider::class,
    App\Providers\AppServiceProvider::class,
];
