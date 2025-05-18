<?php
declare(strict_types=1);
namespace Jolutions\PhpUtils\Authentication\Application;

use Jolutions\PhpUtils\Authentication\Domain\AuthToken;
use Jolutions\PhpUtils\Authentication\Domain\TokenServiceInterface;
use Jolutions\PhpUtils\Authentication\Domain\UserSession;
use PSwag\Authentication\BearerAuthMiddleware;

class TokenBasedBearerAuthMiddleware extends BearerAuthMiddleware
{
    public function __construct(
        private TokenServiceInterface $tokenService,
        private UserSession $userSession,
    ) {}

    public function getBearerFormat(): ?string {
        return null;
    }
    
    public function isBearerTokenValid(string $bearerToken): bool
    {
        $token = AuthToken::fromBearerToken($bearerToken);
        if (!$this->tokenService->isTokenValid($token)) {
            return false;
        }

        $this->tokenService->renewToken($token);
        $this->userSession->setAuthToken($token);
        return true;
    }
}