<?php declare(strict_types=1);

namespace App\Services\CommandBus\DTO;

readonly class CommandContext
{
    public function __construct(
        public string $id,
        public float $createdAt,
        public ?object $payload = null,
    ) {
    }
}
