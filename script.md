# Базовая функциональность бота
## Подготовка
### Устанавливаем и библиотеку для работы с Telegram API

```shell
./vendor/bin/sail composer require irazasyed/telegram-bot-sdk
./vendor/bin/sail artisan vendor:publish --tag="telegram-config"
```

### Объявляем необходимые переменные окружения
```dotenv
TELEGRAM_LOCAL_BOT_TOKEN=
TELEGRAM_BOT_TOKEN=
TELEGRAM_WEBHOOK_URL=
TELEGRAM_ALLOWED_USERS=
```

### Идём в `config/telegram.php` и добавляем недостающие параметры конфигурации
```php
// настройки бота
'local_token' => env('TELEGRAM_LOCAL_BOT_TOKEN'),

// глобальные настройки
'allowed_users' => array_map('trim', explode(',', env('TELEGRAM_ALLOWED_USERS', ''))),
```

### Регистрируем наш вебхук для приёма сообщений в Telegram API
```shell
./vendor/bin/sail artisan telegram:webhook --setup
```

## Создаём тестовую команду
### Создаём каталоги
Создаём каталог `app/Services/Telegram`, в нём создаём каталоги `Commands`, `Enum`, `Jobs`, `Middleware`.

### Добавляем перечисление команд
```php
<?php declare(strict_types = 1);

namespace App\Services\Telegram\Enum;

enum Command: string
{
    case TEST = 'test';
}
```

### Добавляем Middleware для проверки токена и авторизации пользователя
```php
<?php declare(strict_types=1);

namespace App\Services\Telegram\Middleware;

use App\Services\Telegram\Exceptions\AccessDeniedException;
use Psr\Log\LoggerInterface;
use Telegram\Bot\Objects\Update;

readonly class Auth
{
    /**
     * @param int[] $authorizedUserIds
     */
    public function __construct(
        private array $authorizedUserIds,
        private LoggerInterface $logger,
    ) {
    }

    public function handle(Update $update): void
    {
        $fromUserId = $update->message->from->id;
        if (!in_array($fromUserId, $this->authorizedUserIds)) {
            $this->logger->debug("User $fromUserId is not authorized");
            throw new AccessDeniedException();
        }
    }
}

```

```php
<?php declare(strict_types=1);

namespace App\Services\Telegram\Middleware;

use App\Services\Telegram\Exceptions\AccessDeniedException;
use Psr\Log\LoggerInterface;

readonly class LocalTokenVerifier
{
    public function __construct(
        private string $token,
        private LoggerInterface $logger,
    ) {
    }

    public function handle(string $token): void
    {
        if ($this->token !== $token) {
            $this->logger->debug("Invalid local token received $token");
            throw new AccessDeniedException();
        }
    }
}

```

### Создаём сервис для работы с Telegram API
```php
<?php declare(strict_types = 1);

namespace App\Services\Telegram;

use Telegram\Bot\Api;

readonly class Bot
{
    public function __construct(
        private Api $api,
    ) {
    }

    public function handleCommand(bool $webhook = true): void
    {
        $this->api->commandsHandler($webhook);
    }

    public function sendMessage(int $chatId, string $text): void
    {
        $this->api->sendMessage([
            'chat_id' => $chatId,
            'text' => $text,
        ]);
    }
}
```

### Создаём задачу для отправки сообщения
```php
<?php declare(strict_types = 1);

namespace App\Services\Telegram\Jobs;

use App\Services\Telegram\Bot;
use Illuminate\Contracts\Queue\ShouldQueue;

readonly class SendMessage implements ShouldQueue
{
    public function __construct(
        private int $chatId,
        private string $text,
    ) {
    }

    public function handle(Bot $bot): void
    {
        $bot->sendMessage($this->chatId, $this->text);
    }
}
```

### Создаём провайдер для регистрации компонентов сервиса в контейнере
```php
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
```

### Добавляем абстрактную команду
```php
<?php declare(strict_types = 1);

namespace App\Services\Telegram\Commands;

use App\Services\Telegram\Middleware\Auth;
use Telegram\Bot\Commands\Command;
use Illuminate\Contracts\Bus;

abstract class AbstractCommand extends Command
{
    protected \App\Services\Telegram\Enum\Command $command;

    protected string $name {
        get {
            return $this->command->value;
        }
    }

    public function __construct(
        readonly protected Bus\Dispatcher $bus,
        readonly protected Auth $auth,
    ) {
    }

    public function handle(): void
    {
        try {
            $this->auth->handle($this->getUpdate());
        } catch (\Throwable) {
            return;
        }

        $this->process();
    }

    protected function process(): void
    {
        //
    }
}
```

### Создаём саму тестовую команду
```php
<?php declare(strict_types=1);

namespace App\Services\Telegram\Commands;

use App\Services\Telegram\Enum\Command;
use App\Services\Telegram\Jobs\SendMessage;

class TestCommand extends AbstractCommand
{
    protected Command $command = Command::TEST;

    protected function process(): void
    {
        $this->bus->dispatch(new SendMessage(
            (int)$this->getUpdate()->getChat()->get('id'),
            'Current time is ' . new \DateTime()->format(DATE_ATOM),
        ));
    }
}
```

### Регистрируем команду в конфигурации
```php
'commands' => [
    \App\Services\Telegram\Commands\TestCommand::class,
],
```

### Регистрируем провайдеры в Laravel
```php
\Telegram\Bot\Laravel\TelegramServiceProvider::class,
\App\Services\Telegram\TelegramServiceProvider::class,
```

### Объявляем эндпоинт
Создаём файл `routes/api.php`
```php
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
```

и регистрируем его в `bootstrap/app.php`
```php
api: __DIR__ . '/../routes/api.php',
```

### Проверяем работоспособность бота
Открываем чат с ботом в Telegram, отправляем `/test`, убеждаемся в том, что в ответ пришло сообщение с текущим временем.

# Взаимодействие с ESP-8266 через MQTT — поливаем цветы
## Подготовка
### Устанавливаем и регистрируем библиотеку для работы с MQTT
```shell
./vendor/bin/sail composer require php-mqtt/laravel-client
./vendor/bin/sail artisan vendor:publish --provider="PhpMqtt\Client\MqttClientServiceProvider" --tag="config"
```

### Объявляем необходимые переменные окружения
```dotenv
MQTT_HOST=mosquitto
MQTT_AUTH_USERNAME=user
MQTT_AUTH_PASSWORD=1234
MQTT_CLEAN_SESSION=false
MQTT_AUTO_RECONNECT_ENABLED=true
MQTT_ENABLE_LOGGING=false
```

## Создаём сервис для работы с ESP
Создаём каталог `App/Services/PlantRPC`, кладём в него файл конфигурации

```php
<?php

return [
    'mqtt' => [
        'topic_in' => env('PLANT_RPC_TOPIC_IN', 'plant/rpc/in'),
        'topic_out' => env('PLANT_RPC_TOPIC_OUT', 'plant/rpc/out'),
        'topic_events' => env('PLANT_RPC_TOPIC_EVENTS', 'plant/events'),
    ],
    'rpc_max_attempts' => 3,
];
```

Создаём каталог `Enum`, кладём в него файл `RPCMethod.php` — перечисление доступных для вызова процедур (методов)
```php
<?php declare(strict_types = 1);

namespace App\Services\PlantRPC\Enum;

enum RPCMethod: string
{
    case RUN_PUMP = 'runPump';
}
```

### DTO
Создаём каталог `DTO`, кладём в него следующие файлы:

`RPCRequest.php` — будет содержать информацию о параметрах запроса к RPC
```php
<?php declare(strict_types=1);

namespace App\Services\PlantRPC\DTO;

use App\Services\PlantRPC\Enum\RPCMethod;

readonly class RPCRequest implements \JsonSerializable
{
    public function __construct(
        public string $id,
        public RPCMethod $method,
    ) {
    }

    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'method' => $this->method->value,
        ];
    }
}
```

`RPCResponse.php` — будет содержать информацию об ответе RPC
```php
<?php declare(strict_types = 1);

namespace App\Services\PlantRPC\DTO;

readonly class RPCResponse
{
    public function __construct(
        public string $id,
        public bool $ok,
        public ?string $msg,
    ) {
    }

    public static function fromArray(array $payload): static
    {
        return new static(
            $payload['id'],
            $payload['ok'],
            $payload['msg'] ?? null,
        );
    }
}
```

`RunPumpRequest.php` — запрос вызова процедуры `runPump` с дополнительными параметрами
```php
<?php declare(strict_types = 1);

namespace App\Services\PlantRPC\DTO;

use App\Services\PlantRPC\Enum\RPCMethod;

readonly class RunPumpRequest extends RPCRequest
{
    public function __construct(
        string $id,
        public int $pump,
        public int $seconds,
    ) {
        parent::__construct($id, RPCMethod::RUN_PUMP);
    }

    public function jsonSerialize(): array
    {
        return [
            ...parent::jsonSerialize(),
            'params' => [
                'pump' => $this->pump,
                'seconds' => $this->seconds,
            ],
        ];
    }
}
```

`RPCContext.php` — будет содержать информацию о контексте запроса к RPC
```php
<?php declare(strict_types = 1);

namespace App\Services\PlantRPC\DTO;

readonly class RPCContext
{
    public function __construct(
        public string     $id,
        public RPCRequest $request,
        public float      $calledAt,
        public int        $attempts,
    ) {
    }
}
```

### События
Создаём каталог `Events`, кладём в него файл `ResponseReceived.php` — событие получения ответа от RPC
```php
<?php declare(strict_types = 1);

namespace App\Services\PlantRPC\Events;

use App\Services\PlantRPC\DTO\RPCResponse;
use App\Services\PlantRPC\Enum\RPCMethod;

readonly class ResponseReceived
{
    public function __construct(
        public RPCMethod $method,
        public RPCResponse $response,
        public float $elapsedTime,
    ) {
    }
}
```

и `CallExpired.php` — на случай если время ожидания ответа вышло
```php
<?php declare(strict_types = 1);

namespace App\Services\PlantRPC\Events;

use App\Services\PlantRPC\Enum\RPCMethod;

readonly class CallExpired
{
    public function __construct(
        public string $id,
        public RPCMethod $method,
    ) {
    }
}
```

### Сервис
Создаём файл `PlantRPC.php`
```php
<?php declare(strict_types = 1);

namespace App\Services\PlantRPC;

use App\Services\PlantRPC\DTO\RPCContext;
use App\Services\PlantRPC\DTO\RPCRequest;
use App\Services\PlantRPC\DTO\RPCResponse;
use App\Services\PlantRPC\Enum\RPCMethod;
use App\Services\PlantRPC\Events\CallExpired;
use App\Services\PlantRPC\Events\ResponseReceived;
use App\Services\PlantRPC\Jobs\CheckPengingContext;
use Illuminate\Contracts\Bus;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Events;
use PhpMqtt\Client\ConnectionManager;
use PhpMqtt\Client\Contracts\MqttClient;
use Psr\Log\LoggerInterface;

readonly class PlantRPC
{
    private const string RPC_CONTEXT_CACHE_KEY = 'rpc_ctx_%s';
    private const int RPC_CONTEXT_CACHE_TTL = 10;
    private const int RPC_CALL_TIMEOUT = 2;

    public function __construct(
        private ConnectionManager $connectionManager,
        private Repository $cache,
        private Bus\Dispatcher $bus,
        private Events\Dispatcher $events,
        private LoggerInterface $logger,
        private string $topicIn,
        private string $topicOut,
        private int $maxAttempts,
    ) {
    }

    public function listenForResponses(): void
    {
        $client = $this->connectionManager->connection();

        $client->registerMessageReceivedEventHandler(
            fn(MqttClient $client, string $topic, string $message) => match ($topic) {
                $this->topicOut => $this->handleRpcResponse($message),
            }
        );

        $client->subscribe($this->topicOut, qualityOfService: 2);

        $this->logger->info('Listening for RPC messages');

        $client->loop();
    }

    public function stopListeningForResponses(): void
    {
        $this->connectionManager->connection()->interrupt();
        $this->logger->info('Done');
    }

    public function call(RPCRequest $request): void
    {
        $client = $this->connectionManager->connection();

        $client->publish(
            $this->topicIn,
            json_encode($request)
        );

        $this->storeContext($request);

        $this->bus->dispatch(
            new CheckPengingContext($request->id)
                ->delay(self::RPC_CALL_TIMEOUT)
        );
    }

    public function checkPendingContext(string $id): void
    {
        $ctx = $this->getContext($id);
        if (null !== $ctx) {
            if ($ctx->attempts >= $this->maxAttempts) {
                $this->events->dispatch(new CallExpired($id, $ctx->request->method));
                $this->clearContext($id);
            } else {
                $this->call($ctx->request);
            }
        }
    }

    private function handleRpcResponse(string $rawResponse): void
    {
        $decoded = json_decode($rawResponse, true);
        $id = $decoded['id'];

        $this->logger->info("Got RPC response for $id");

        $ctx = $this->getContext($id);

        if (null !== $ctx) {
            $dto = RPCResponse::fromArray($decoded);

            $this->events->dispatch(
                new ResponseReceived(
                    $ctx->request->method,
                    $dto,
                    microtime(true) - $ctx->calledAt
                )
            );

            $this->clearContext($id);
        }
    }

    private function getContext(string $id): ?RPCContext
    {
        return $this->cache->get(sprintf(self::RPC_CONTEXT_CACHE_KEY, $id));
    }

    private function clearContext(string $id): void
    {
        $this->cache->delete(sprintf(self::RPC_CONTEXT_CACHE_KEY, $id));
    }

    private function storeContext(RPCRequest $request): void
    {
        $existingContext = $this->getContext($request->id);
        if (null !== $existingContext) {
            $this->clearContext($request->id);
        }

        $this->cache->set(
            sprintf(self::RPC_CONTEXT_CACHE_KEY, $request->id),
            new RPCContext(
                $request->id,
                $request,
                microtime(true),
                ($existingContext?->attempts ?? 0) + 1
            ),
            self::RPC_CONTEXT_CACHE_TTL
        );
    }
}
```

### Асинхронные задачи
Создаём каталог `Jobs`, кладём в него файлы
`PerformCall.php` — задача на выполнение вызова RPC
```php
<?php declare(strict_types = 1);

namespace App\Services\PlantRPC\Jobs;

use App\Services\PlantRPC\DTO\RPCRequest;
use App\Services\PlantRPC\PlantRPC;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class PerformCall implements ShouldQueue
{
    use Queueable;

    public function __construct(
        readonly public RPCRequest $request,
    ) {
    }

    public function handle(PlantRPC $plantRPC): void
    {
        $plantRPC->call($this->request);
    }
}
```

`CheckPendingContext.php` — задача на проверку состояния ожидающих ответа запросов
```php
<?php declare(strict_types=1);

namespace App\Services\PlantRPC\Jobs;

use App\Services\PlantRPC\PlantRPC;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class CheckPendingContext implements ShouldQueue
{
    use Queueable;

    public function __construct(
        readonly public string $id,
    ) {
    }

    public function handle(PlantRPC $plantRPC): void
    {
        $plantRPC->checkPendingContext($this->id);
    }
}
```

### Провайдер
Регистрируем сервис в Laravel. Для этого создаём провайдер и указываем его в `bootstrap/providers.php`
```php
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
```
