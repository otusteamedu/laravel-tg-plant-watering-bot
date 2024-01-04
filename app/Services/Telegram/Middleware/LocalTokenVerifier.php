<?php declare(strict_types=1);

namespace App\Services\Telegram\Middleware;

use App\Services\Telegram\Exceptions\AccessDeniedException;
use Psr\Log\LoggerInterface;

readonly class LocalTokenVerifier
{
    public function __construct(
        private string $token,
        private LoggerInterface $logger,
    ) {
    }

    public function handle(string $token): void
    {
        if ($this->token !== $token) {
            $this->logger->debug("Invalid local token received $token");
            throw new AccessDeniedException();
        }
    }
}
