<?php declare(strict_types = 1);

namespace App\Services\CommandBus;

use App\Services\CommandBus\DTO\CommandContext;
use App\Services\CommandBus\Events\ContextCompleted;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Events\Dispatcher;

/**
 * Здесь вся обработка контекста команды
 */
readonly class ContextManager
{
    private const string CONTEXT_CACHE_KEY = __CLASS__ . 'cmd_ctx_%s';
    private const int CONTEXT_CACHE_TTL = 30;

    public function __construct(
        private Dispatcher $events,
        private Repository $cache,
    ) {
    }

    public function createContext(?object $payload = null): CommandContext
    {
        $ctx = new CommandContext(
            uniqid(more_entropy: true),
            microtime(true),
            $payload,
        );

        $this->cache->set(
            $this->getCtxKey($ctx->id),
            $ctx,
            self::CONTEXT_CACHE_TTL
        );

        return $ctx;
    }

    public function getContext(string $id): ?CommandContext
    {
        return $this->cache->get($this->getCtxKey($id));
    }

    public function clearContext(CommandContext $ctx): void
    {
        $this->cache->delete($this->getCtxKey($ctx->id));
        $this->events->dispatch(new ContextCompleted(
            $ctx,
            microtime(true)
        ));
    }

    private function getCtxKey(string $id): string
    {
        return sprintf(self::CONTEXT_CACHE_KEY, $id);
    }
}
