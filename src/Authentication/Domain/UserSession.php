<?php
declare(strict_types=1);
namespace Jolutions\PhpUtils\Authentication\Domain;

class UserSession
{
    private ?int $userId = null;
    private ?string $authToken = null;

    public function setSession(?int $userId, ?string $authToken): void {
        $this->userId = $userId;
        $this->authToken = $authToken;
    }

    public function getUserId(): ?int {
        return $this->userId;
    }

    public function getAuthToken(): ?string {
        return $this->authToken;
    }
}