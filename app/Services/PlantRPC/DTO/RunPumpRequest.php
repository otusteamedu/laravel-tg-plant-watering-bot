<?php declare(strict_types = 1);

namespace App\Services\PlantRPC\DTO;

use App\Services\PlantRPC\Enum\RPCMethod;

readonly class RunPumpRequest extends RPCRequest
{
    public function __construct(
        string $id,
        public int $pump,
        public int $seconds,
    ) {
        parent::__construct($id, RPCMethod::RUN_PUMP);
    }

    public function jsonSerialize(): array
    {
        return [
            ...parent::jsonSerialize(),
            'params' => [
                'pump' => $this->pump,
                'seconds' => $this->seconds,
            ],
        ];
    }
}
