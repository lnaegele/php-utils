<?PHP
declare(strict_types=1);
namespace Jolutions\PhpUtils\Mail;

class PhpMailService implements MarkdownMailServiceInterface
{
    public function __construct(
        private string $senderEmail,
    ) {}

    public function sendMail(string $email, string $subject, string $message, bool $isHtml = false): void
    {
        // To send HTML mail, the Content-type header must be set
        $mailHeaders = ($isHtml ? "MIME-Version: 1.0\r\nContent-type: text/html; charset=utf-8" : "Content-type: text/plain; charset=utf-8") . "\r\n" .
            'From: ' . $this->senderEmail . "\r\n" .
            'Reply-To: ' . $this->senderEmail . "\r\n" .
            'Return-Path: ' . $this->senderEmail;
        if (!mail($email, $subject, $message, $mailHeaders)) {
            throw new \Exception("Could not send email to $email.");
        }
    }

    public function sendMarkdownMail(string $email, string $subject, string $markdown, bool $isHtml = true): void
    {
        // mail() offers no multipart support, so HTML mails are sent without plain text alternative
        $converter = new MarkdownConverter();
        $this->sendMail($email, $subject, $isHtml ? $converter->toHtml($markdown) : $converter->toText($markdown), $isHtml);
    }
}