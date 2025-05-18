<?PHP
declare(strict_types=1);
namespace Jolutions\PhpUtils\Mail;

use Jolutions\PhpUtils\Authentication\Domain\MailServiceInterface;
use PHPMailer\PHPMailer\PHPMailer;

class SmtpMailService implements MailServiceInterface
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

        if ($isHtml) {
            // This will call isHTML, set Body to the HTML you provide (after some processing to handle images), and strip tags and use it to set AltBody.
            $mail->msgHtml($message);
        } else {
            $mail->Body = $message;
        }

        if(!$mail->send())
        {
            throw new \Exception("Could not send email to $email. Mailer Error: $mail->ErrorInfo"); 
        }
    }
}