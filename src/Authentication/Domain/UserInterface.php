<?php
declare(strict_types=1);
namespace Jolutions\PhpUtils\Authentication\Domain;

interface UserInterface
{
    public function getId(): int;
    
    public function getFullUserName(): ?string;

    public function getEmail(): string;
    public function setEmail(string $email): void;

    public function isEmailConfirmed(): bool;
    public function setEmailConfirmed(bool $isEmailConfirmed): void;

    public function getPasswordHash(): ?string;
    public function setPasswordHash(?string $passwordHash): void;

    public function getReplacementEmail(): ?string;
    public function setReplacementEmail(?string $replacementEmail): void;

    public function getConfirmationToken(): ?string;
    public function setConfirmationToken(?string $confirmationToken): void;

    public function getConfirmationTokenValidUntil(): ?\DateTime;
    public function setConfirmationTokenValidUntil(?\DateTime $confirmationTokenValidUntil): void;
}