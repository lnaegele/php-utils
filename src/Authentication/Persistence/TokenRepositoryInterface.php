<?php
declare(strict_types=1);
namespace Jolutions\PhpUtils\Authentication\Persistence;

interface TokenRepositoryInterface
{
    public function createToken(int $userId): string;

    public function validateTokenAndGetUserId(string $token): int|bool;

    public function renewToken(string $token): void;

    public function invalidateToken(string $token): void;
}