<?php
declare(strict_types=1);
namespace Jolutions\PhpUtils\Authentication\Domain;

class UserSession
{
    private ?AuthToken $authToken = null;

    public function setAuthToken(?AuthToken $authToken): void {
        $this->authToken = $authToken;
    }

    public function getAuthToken(): ?AuthToken {
        return $this->authToken;
    }

    public function getUserId(): ?int {
        return $this->authToken?->getUserId();
    }
}