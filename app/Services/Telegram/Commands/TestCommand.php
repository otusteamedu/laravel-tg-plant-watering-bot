<?php declare(strict_types=1);

namespace App\Services\Telegram\Commands;

use App\Services\Telegram\Enum\Command;

class TestCommand extends AbstractCommand
{
    protected Command $command = Command::TEST;
}
