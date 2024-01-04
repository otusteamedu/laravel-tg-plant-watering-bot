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
