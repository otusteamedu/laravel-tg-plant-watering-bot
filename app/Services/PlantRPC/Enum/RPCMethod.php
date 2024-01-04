<?php declare(strict_types = 1);

namespace App\Services\PlantRPC\Enum;

enum RPCMethod: string
{
    case RUN_PUMP = 'runPump';
    case GET_TEMP = 'getAmbientTemp';
    case GET_HUM = 'getAmbientHum';
}
