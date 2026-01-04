<?php
declare(strict_types=1);
namespace Jolutions\PhpUtils\Authentication\Application;

use Jolutions\PhpUtils\Authentication\Domain\UserSession;
use Jolutions\PhpUtils\Authentication\Persistence\TokenRepositoryInterface;
use PSwag\Authentication\BearerAuthMiddleware;

class TokenBasedBearerAuthMiddleware extends BearerAuthMiddleware
{
    public function __construct(
        private TokenRepositoryInterface $tokenRepository,
        private UserSession $userSession,
    ) {}

    public function getBearerFormat(): ?string {
        return null;
    }
    
    public function isBearerTokenValid(string $bearerToken): bool
    {
        $userId = $this->tokenRepository->validateTokenAndGetUserId($bearerToken);
        if ($userId===false) return false;

        $this->tokenRepository->renewToken($bearerToken);
        $this->userSession->setSession($userId, $bearerToken);
        return true;
    }
}