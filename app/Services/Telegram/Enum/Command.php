<?php declare(strict_types = 1);

namespace App\Services\Telegram\Enum;

enum Command: string
{
    case TEST = 'test';
    case RUN_PUMP = 'runPump';
    case GET_TEMP = 'getTemp';
    case GET_HUM = 'getHum';
}
