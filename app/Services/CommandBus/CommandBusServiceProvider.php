<?php declare(strict_types = 1);

namespace App\Services\CommandBus;

use Illuminate\Support\ServiceProvider;

class CommandBusServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ContextManager::class);
    }
}
