<?php declare(strict_types = 1);

namespace App\Services\CommandBus\Events;

use App\Services\CommandBus\DTO\CommandContext;

readonly class ContextCompleted
{
    public function __construct(
        public CommandContext $ctx,
        public float $completedAt,
    ) {
    }
}
