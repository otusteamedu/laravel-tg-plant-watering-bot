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
