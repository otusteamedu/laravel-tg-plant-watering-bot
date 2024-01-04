<?php declare(strict_types=1);

namespace App\Services\Telegram\Commands;

use App\Services\Telegram\Enum\Command;

class GetTemp extends AbstractCommand
{
    protected Command $command = Command::GET_TEMP;
    protected string $description = 'Get temperature';
}
