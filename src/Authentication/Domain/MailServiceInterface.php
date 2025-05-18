<?php
declare(strict_types=1);
namespace Jolutions\PhpUtils\Authentication\Domain;

interface MailServiceInterface
{
    public function sendMail(string $email, string $subject, string $message, bool $isHtml = false): void;
}