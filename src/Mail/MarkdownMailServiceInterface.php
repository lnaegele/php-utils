<?php
declare(strict_types=1);
namespace Jolutions\PhpUtils\Mail;

interface MarkdownMailServiceInterface extends MailServiceInterface
{
    /**
     * Sends a mail whose content is written in Markdown (see MarkdownConverter for the supported syntax).
     * HTML mails contain the rendered HTML plus a plain text alternative, text mails contain the plain text rendering only.
     * Use MarkdownConverter::escape() for dynamic content that must not be interpreted as Markdown.
     */
    public function sendMarkdownMail(string $email, string $subject, string $markdown, bool $isHtml = true): void;
}
