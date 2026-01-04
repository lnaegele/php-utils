<?php
declare(strict_types=1);
namespace Jolutions\PhpUtils\Authentication\Domain;

trait UserTrait
{
    public string $email;
    public bool $isEmailConfirmed;
    public ?string $passwordHash;
    public ?string $replacementEmail;
    public ?string $confirmationToken;
    public ?\DateTime $confirmationTokenValidUntil;

    public abstract function getId(): int;

    public abstract function getFullUserName(): ?string;
    

    public function getEmail(): string {
        return $this->email;
    }

    public function setEmail(string $email): void {
        $this->email = $email;
    }

    public function isEmailConfirmed(): bool {
        return $this->isEmailConfirmed;
    }

    public function setEmailConfirmed(bool $isEmailConfirmed): void {
        $this->isEmailConfirmed = $isEmailConfirmed;
    }

    public function getPasswordHash(): ?string {
        return $this->getPasswordHash;
    }

    public function setPasswordHash(?string $passwordHash): void {
        $this->passwordHash = $passwordHash;
    }

    public function getReplacementEmail(): ?string {
        return $this->replacementEmail;
    }

    public function setReplacementEmail(?string $replacementEmail): void {
        $this->replacementEmail = $replacementEmail;
    }

    public function getConfirmationToken(): ?string {
        return $this->confirmationToken;
    }

    public function setConfirmationToken(?string $confirmationToken): void {
        $this->confirmationToken = $confirmationToken;
    }

    public function getConfirmationTokenValidUntil(): ?\DateTime {
        return $this->confirmationTokenValidUntil;
    }

    public function setConfirmationTokenValidUntil(?\DateTime $confirmationTokenValidUntil): void {
        $this->confirmationTokenValidUntil = $confirmationTokenValidUntil;
    }
}