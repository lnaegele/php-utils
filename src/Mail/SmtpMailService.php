<?PHP
declare(strict_types=1);
namespace Jolutions\PhpUtils\Mail;

use PHPMailer\PHPMailer\PHPMailer;

/**
 * Sends mails via SMTP. Encryption is chosen automatically, see SmtpMailerFactory::create().
 */
class SmtpMailService implements MarkdownMailServiceInterface
{
    public function __construct(
        private string $senderEmail,
        private string $senderName,
        private string $smtpHost,
        private int $smtpPort,
        private string $smtpUsername,
        private string $smtpPassword,
    ) {}

    public function sendMail(string $email, string $subject, string $message, bool $isHtml = false): void
    {
        $this->send($email, $subject, function (PHPMailer $mail) use ($message, $isHtml) {
            if ($isHtml) {
                // This will call isHTML, set Body to the HTML you provide (after some processing to handle images), and strip tags and use it to set AltBody.
                $mail->msgHtml($message);
            } else {
                $mail->Body = $message;
            }
        });
    }

    public function sendMarkdownMail(string $email, string $subject, string $markdown, bool $isHtml = true): void
    {
        $converter = new MarkdownConverter();
        $this->send($email, $subject, function (PHPMailer $mail) use ($converter, $markdown, $isHtml) {
            if ($isHtml) {
                $mail->isHTML(true);
                $mail->Body = $converter->toHtml($markdown);
                $mail->AltBody = $converter->toText($markdown);
            } else {
                $mail->Body = $converter->toText($markdown);
            }
        });
    }

    /**
     * @param callable(PHPMailer): void $setContent
     */
    private function send(string $email, string $subject, callable $setContent): void
    {
        try {
            $mail = SmtpMailerFactory::create($this->smtpHost, $this->smtpPort, $this->smtpUsername, $this->smtpPassword);
            $mail->CharSet = PHPMailer::CHARSET_UTF8;
            $mail->setFrom($this->senderEmail, $this->senderName, false);
            $mail->addAddress($email);
            $mail->Subject = $subject;
            $setContent($mail);
            $mail->send();
        } catch (\Throwable $e) {
            throw new \Exception("Could not send email to $email. Mailer Error: " . $e->getMessage(), 0, $e);
        }
    }
}
