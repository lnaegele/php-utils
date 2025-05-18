<?php
declare(strict_types=1);
namespace Jolutions\PhpUtils\Authentication\Domain;

interface TokenServiceInterface
{
    public function createToken(int $userId): AuthToken;

    public function isTokenValid(AuthToken $token): bool;

    public function renewToken(AuthToken $token): void;

    public function invalidateToken(AuthToken $token): void;
}