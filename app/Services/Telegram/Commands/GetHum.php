<?php declare(strict_types=1);

namespace App\Services\Telegram\Commands;

use App\Services\Telegram\Enum\Command;

class GetHum extends AbstractCommand
{
    protected Command $command = Command::GET_HUM;
    protected string $description = 'Get humidity';
}
