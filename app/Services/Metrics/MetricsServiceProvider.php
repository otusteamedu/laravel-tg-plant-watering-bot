<?php declare(strict_types=1);

namespace App\Services\Metrics;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;
use InfluxDB2\Client;

class MetricsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            Client::class,
            static fn(Container $container): Client => new Client(
                $container->make(Repository::class)->get('services.influxdb.client')
            )
        );

        $this->app->singleton(MetricsRepository::class);
        $this->app->when(MetricsRepository::class)
            ->needs('$defaultBucket')
            ->give(
                static fn(Container $container): string => $container->make(Repository::class)
                    ->get('services.influxdb.client.bucket')
            );
    }
}
