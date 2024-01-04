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
