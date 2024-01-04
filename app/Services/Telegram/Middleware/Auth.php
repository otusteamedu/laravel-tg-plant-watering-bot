<?php declare(strict_types=1);

namespace App\Services\Telegram\Middleware;

use App\Services\Telegram\Exceptions\AccessDeniedException;
use Psr\Log\LoggerInterface;
use Telegram\Bot\Objects\Update;

readonly class Auth
{
    /**
     * @param int[] $authorizedUserIds
     */
    public function __construct(
        private array $authorizedUserIds,
        private LoggerInterface $logger,
    ) {
    }

    public function handle(Update $update): void
    {
        $fromUserId = $update->message->from->id;
        if (!in_array($fromUserId, $this->authorizedUserIds)) {
            $this->logger->debug("User $fromUserId is not authorized");
            throw new AccessDeniedException();
        }
    }
}
