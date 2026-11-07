<?PHP
declare(strict_types=1);
namespace Jolutions\PhpUtils\Mail;

use PHPMailer\PHPMailer\PHPMailer;

/**
 * Creates PHPMailer instances with a consistent SMTP transport configuration (encryption, authentication, timeouts).
 * Message related settings (sender, recipients, content, charset, ...) are left to the caller.
 */
class SmtpMailerFactory
{
    /** Seconds to wait for connecting and for each server reply (PHPMailer default: 300) */
    public const DEFAULT_TIMEOUT = 15;

    /**
     * Encryption is chosen automatically:
     * - port 465: implicit TLS (SMTPS), PHPMailer does not detect this by itself
     * - localhost: STARTTLS if offered by the server
     * - otherwise: STARTTLS is required, so credentials are never sent unencrypted
     *
     * The returned PHPMailer throws exceptions on errors.
     */
    public static function create(string $host, int $port, string $username, string $password, int $timeout = self::DEFAULT_TIMEOUT): PHPMailer
    {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $host;
        $mail->Port = $port;

        if ($port === 465) {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else if (self::isLocalHost($host)) {
            $mail->SMTPSecure = '';
            $mail->SMTPAutoTLS = true;
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        $mail->SMTPAuth = $username !== '';
        $mail->Username = $username;
        $mail->Password = $password;

        // Timeout only limits connecting, Timelimit limits waiting for each server reply
        $mail->Timeout = $timeout;
        $mail->getSMTPInstance()->Timelimit = $timeout;

        return $mail;
    }

    /**
     * Tries to connect and authenticate, using the same transport configuration as create().
     * @return ?string null on success, the error message otherwise
     */
    public static function testConnection(string $host, int $port, string $username, string $password, int $timeout = self::DEFAULT_TIMEOUT): ?string
    {
        $mail = self::create($host, $port, $username, $password, $timeout);
        try {
            return $mail->smtpConnect() ? null : 'Verbindung fehlgeschlagen';
        } catch (\Throwable $e) {
            return $e->getMessage();
        } finally {
            $mail->smtpClose();
        }
    }

    private static function isLocalHost(string $host): bool
    {
        return in_array(strtolower(trim($host)), ['localhost', '127.0.0.1', '::1', '[::1]'], true);
    }
}
