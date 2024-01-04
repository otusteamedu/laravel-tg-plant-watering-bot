<?php declare(strict_types=1);

namespace App\Services\Telegram;

use App\Services\Telegram\Middleware\Auth;
use App\Services\Telegram\Middleware\LocalTokenVerifier;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;

class TelegramServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Auth::class);
        $this->app->when(Auth::class)
            ->needs('$authorizedUserIds')
            ->give(
                static fn(Container $container): array => $container
                    ->make(Repository::class)
                    ->get('telegram.allowed_users')
            );

        $this->app->singleton(LocalTokenVerifier::class);
        $this->app->when(LocalTokenVerifier::class)
            ->needs('$token')
            ->give(
                static fn(Container $container): string => $container
                    ->make(Repository::class)
                    ->get('telegram.bots.mybot.local_token')
            );
    }
}
