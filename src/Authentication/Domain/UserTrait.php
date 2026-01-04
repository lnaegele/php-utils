<?php
declare(strict_types=1);
namespace Jolutions\PhpUtils\Authentication\Domain;

trait UserTrait
{
    public string $email;
    public bool $isEmailConfirmed = false;
    public ?string $passwordHash;
    public ?string $replacementEmail;
    public ?string $confirmationToken;
    public ?\DateTime $confirmationTokenValidUntil;

    public abstract function getId(): int;
    public abstract function getFullUserName(): ?string;
}