<?php
declare(strict_types=1);
namespace Jolutions\PhpUtils\Authentication\Persistence;

use Jolutions\PhpUtils\Authentication\Domain\UserTrait;

interface UserRepositoryInterface
{
    public function getUserByIdOrNull(int $id): ?UserTrait;
    
    public function getUserByEmailOrNull(string $email): ?UserTrait;

    public function updateUser(UserTrait $user): void;
}