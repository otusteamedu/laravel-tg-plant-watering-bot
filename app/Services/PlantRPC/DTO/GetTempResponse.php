<?php declare(strict_types = 1);

namespace App\Services\PlantRPC\DTO;

readonly class GetTempResponse extends RPCResponse
{
    public function __construct(
        string $id,
        bool $ok,
        ?string $msg,
        public float $value,
    ) {
        parent::__construct($id, $ok, $msg);
    }

    public static function fromArray(array $payload): static
    {
        return new self(
            $payload['id'],
            $payload['ok'],
            $payload['msg'] ?? null,
            $payload['data']['temperature'] ?? 0,
        );
    }
}
