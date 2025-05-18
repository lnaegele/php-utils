<?PHP
declare(strict_types=1);
namespace Jolutions\PhpUtils\Mail;

use Jolutions\PhpUtils\Authentication\Domain\MailServiceInterface;

class MailService implements MailServiceInterface
{
    public function __construct(
        private string $senderEmail,
    ) {}

    public function sendMail(string $email, string $subject, string $message, bool $isHtml = false): void
    {
        $mailHeaders = 'From: ' . $this->senderEmail . "\r\n" .
            'Reply-To: ' . $this->senderEmail . "\r\n" .
            'Return-Path: ' . $this->senderEmail;       
        if (!mail($email, $subject, $message, $mailHeaders)) {
            throw new \Exception("Could not send email to $email.");
        }
    }
}