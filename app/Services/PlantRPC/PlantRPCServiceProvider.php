<?php declare(strict_types = 1);

namespace App\Services\PlantRPC;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class PlantRPCServiceProvider extends ServiceProvider implements DeferrableProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/config.php', 'plant-rpc');

        $this->app->singleton(PlantRPC::class);
        $this->app->when(PlantRPC::class)
            ->needs('$topicIn')
            ->give(
                static fn(Container $container): string => $container->make(Repository::class)
                    ->get('plant-rpc.mqtt.topic_in')
            );
        $this->app->when(PlantRPC::class)
            ->needs('$topicOut')
            ->give(
                static fn(Container $container): string => $container->make(Repository::class)
                    ->get('plant-rpc.mqtt.topic_out')
            );
        $this->app->when(PlantRPC::class)
            ->needs('$maxAttempts')
            ->give(
                static fn(Container $container): int => $container->make(Repository::class)
                    ->get('plant-rpc.rpc_max_attempts')
            );
    }

    public function provides(): array
    {
        return [
            PlantRPC::class,
        ];
    }
}
