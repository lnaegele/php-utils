<?php
declare(strict_types=1);
namespace Jolutions\PhpUtils\Authentication\Domain;

class AuthToken
{
    private int $userId;
    private string $hash;

    public function __construct(int $userId, string $hash)
    {
        $this->userId = $userId;
        $this->hash = $hash;
    }

    public static function fromBearerToken(string $bearerToken): AuthToken {
        $parts = explode('|', base64_decode($bearerToken));
        if (count($parts) != 2) throw new \Exception('Invalid bearer token format');
        return new AuthToken(intval($parts[0]), $parts[1]);
    }

    public function getUserId(): int {
        return $this->userId;
    }

    public function getHash(): string {
        return $this->hash;
    }

    public function __toString(): string
    {
        return base64_encode($this->userId . '|' . $this->hash);
    }
}