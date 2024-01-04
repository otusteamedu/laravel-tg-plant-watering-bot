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
