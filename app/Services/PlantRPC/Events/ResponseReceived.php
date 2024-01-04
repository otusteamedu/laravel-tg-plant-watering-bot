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
