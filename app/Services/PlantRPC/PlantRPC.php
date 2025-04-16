<?php declare(strict_types = 1);

namespace App\Services\PlantRPC;

use App\Services\PlantRPC\DTO\GetHumResponse;
use App\Services\PlantRPC\DTO\GetTempResponse;
use App\Services\PlantRPC\DTO\RPCContext;
use App\Services\PlantRPC\DTO\RPCRequest;
use App\Services\PlantRPC\DTO\RPCResponse;
use App\Services\PlantRPC\Enum\RPCMethod;
use App\Services\PlantRPC\Events\CallExpired;
use App\Services\PlantRPC\Events\ResponseReceived;
use App\Services\PlantRPC\Jobs\CheckPendingContext;
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

    /**
     * Подключаемся к MQTT и слушаем топик $topicOut, обрабатывая все входящие сообщения через handleRpcResponse
     */
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

    /**
     * Прекращаем слушать топик и обрабатывать сообщения
     */
    public function stopListeningForResponses(): void
    {
        $this->connectionManager->connection()->interrupt();
        $this->logger->info('Done');
    }

    /**
     * Вызываем RPC
     */
    public function call(RPCRequest $request): void
    {
        /**
         * Подключаемся к MQTT
         */
        $client = $this->connectionManager->connection();

        /**
         * Кладём JSON с запросом в топик $topicIn
         */
        $client->publish(
            $this->topicIn,
            json_encode($request),
            2
        );
        /**
         * Ждём, пока сообщение не будет доставлено
         */
        $client->loop(true, true);

        /**
         * Сохраняем контекст запроса (НЕ имеет отношения к контексту команды и сервису CommandBus!)
         */
        $this->storeContext($request);

        /**
         * Создаём отложенную задачу для проверки зависшего запроса
         */
        $this->bus->dispatch(
            new CheckPendingContext($request->id)
                ->delay(self::RPC_CALL_TIMEOUT)
        );
    }

    /**
     * Проверяем зависший запрос
     */
    public function checkPendingContext(string $id): void
    {
        $ctx = $this->getContext($id);
        if (null !== $ctx) {
            if ($ctx->attempts >= $this->maxAttempts) {
                /**
                 * Если попыток было больше положенного, порождаем событие об истекшем контексте, а сам контекст удаляем
                 */
                $this->events->dispatch(new CallExpired($id, $ctx->request->method));
                $this->clearContext($id);
            } else {
                /**
                 * Иначе пробуем отправить запрос повторно
                 */
                $this->call($ctx->request);
            }
        }
    }

    /**
     * Обрабатываем ответ от PlantRPC
     */
    private function handleRpcResponse(string $rawResponse): void
    {
        $decoded = json_decode($rawResponse, true);
        $id = $decoded['id'];

        $this->logger->info("Got RPC response for $id");
        /**
         * Достаём из кеша контекст по $id
         */
        $ctx = $this->getContext($id);

        if (null !== $ctx) {
            /**
             * Формируем DTO в зависимости от метода запроса и отправляем событие об успешном получении ответа
             */
            $dto = match ($ctx->request->method) {
                RPCMethod::GET_TEMP => GetTempResponse::fromArray($decoded),
                RPCMethod::GET_HUM => GetHumResponse::fromArray($decoded),
                default => RPCResponse::fromArray($decoded),
            };

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
