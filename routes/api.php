<?php declare(strict_types=1);

\Illuminate\Support\Facades\Route::post(
    '/tg/{token}',
    function (
        \App\Services\Telegram\Bot $bot,
        \App\Services\Telegram\Middleware\LocalTokenVerifier $localTokenVerifier,
        string $token
    ): void {
        try {
            $localTokenVerifier->handle($token);
        } catch (Throwable) {
            return;
        }

        $bot->handleCommand();
    }
);
