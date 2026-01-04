<?php
declare(strict_types=1);
namespace Jolutions\PhpUtils\Authentication\Persistence;

use Jolutions\PhpUtils\Authentication\Domain\UserInterface;

/**
 * @template T of UserInterface
 */
interface UserRepositoryInterface
{
    /**
     * @return ?T
     */
    public function getUserByIdOrNull(int $id): ?UserInterface;
    
    /**
     * @return ?T
     */
    public function getUserByEmailOrNull(string $email): ?UserInterface;

    /**
     * @param T $user
     */
    public function updateUser(UserInterface $user): void;
}