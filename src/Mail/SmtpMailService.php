<?PHP
declare(strict_types=1);
namespace Jolutions\PhpUtils\Mail;

use PHPMailer\PHPMailer\PHPMailer;

class SmtpMailService implements MarkdownMailServiceInterface
{
    public function __construct(
        private string $senderEmail,
        private string $senderName,
        private string $smtpHost,
        private int $smtpPort,
        private string $smtpUsername,
        private string $smtpPassword,
        private bool $smtpTls = true,
    ) {}

    public function sendMail(string $email, string $subject, string $message, bool $isHtml = false): void
    {
        $mail = $this->createMailer($email, $subject);

        if ($isHtml) {
            // This will call isHTML, set Body to the HTML you provide (after some processing to handle images), and strip tags and use it to set AltBody.
            $mail->msgHtml($message);
        } else {
            $mail->Body = $message;
        }

        $this->send($mail, $email);
    }

    public function sendMarkdownMail(string $email, string $subject, string $markdown, bool $isHtml = true): void
    {
        $converter = new MarkdownConverter();
        $mail = $this->createMailer($email, $subject);

        if ($isHtml) {
            $mail->isHTML(true);
            $mail->Body = $converter->toHtml($markdown);
            $mail->AltBody = $converter->toText($markdown);
        } else {
            $mail->Body = $converter->toText($markdown);
        }

        $this->send($mail, $email);
    }

    private function createMailer(string $email, string $subject): PHPMailer
    {
        $mail = new PHPMailer();
        $mail->isSMTP();
        $mail->Host = $this->smtpHost;
        $mail->SMTPAuth = true;
        $mail->Username = $this->smtpUsername;
        $mail->Password = $this->smtpPassword;
        $mail->SMTPSecure = $this->smtpTls ? "tls" : "";
        $mail->Port = $this->smtpPort;
        $mail->From = $this->senderEmail;
        $mail->FromName = $this->senderName;
        $mail->addAddress($email, "");
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        return $mail;
    }

    private function send(PHPMailer $mail, string $email): void
    {
        if(!$mail->send())
        {
            throw new \Exception("Could not send email to $email. Mailer Error: $mail->ErrorInfo");
        }
    }
}