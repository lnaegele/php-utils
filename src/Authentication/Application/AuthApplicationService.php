<?PHP
declare(strict_types=1);
namespace Jolutions\PhpUtils\Authentication\Application;

use Jolutions\PhpUtils\Appsettings\AppsettingsManager;
use Psr\Log\LoggerInterface;
use Jolutions\PhpUtils\Authentication\Domain\MailServiceInterface;
use Jolutions\PhpUtils\Authentication\Domain\TokenServiceInterface;
use Jolutions\PhpUtils\Authentication\Domain\UserServiceInterface;
use Jolutions\PhpUtils\Authentication\Domain\UserSession;
use Jolutions\PhpUtils\UserFriendlyError\Application\UserFriendlyException;

class AuthApplicationService
{
    private const APPSETTINGS_FRONTENDURL_KEY = 'frontendUrl';
    private const APPSETTINGS_EMAILGREETING_KEY = 'emailGreeting';

    public function __construct(
        private UserSession $userSession,
        private UserServiceInterface $userService,
        private TokenServiceInterface $tokenService,
        private MailServiceInterface $mailService,
        private LoggerInterface $logger,
        private AppsettingsManager $appsettingsManager,
    ) {}

    public function login(string $email, string $password) : string
    {
        $isPasswordExisting = $this->userService->isPasswordExisting($email);
        if ($isPasswordExisting===false) {
            $this->createPasswordResetTokenAndSendEmail($email, false);
            throw new UserFriendlyException("Email-Bestätigung ist noch ausstehend. Eine Email wurde an die hinterlegte Email-Adresse versandt.", 403);            
        }

        $userId = $this->userService->getUserIdByCredentials($email, $password);
        if ($userId == null) {
            throw new UserFriendlyException("Falsche Login-Daten.", 401);
        }
        
        $token = $this->tokenService->createToken($userId);
        return $token->__toString();
    }

    public function logout() : void
    {
        $authToken = $this->userSession->getAuthToken();
        if ($authToken != null) {
            $this->tokenService->invalidateToken($authToken);
        }
    }

    public function triggerResetPassword(string $email): void {
        $this->createPasswordResetTokenAndSendEmail($email, false);
    }

    public function createPasswordResetTokenAndSendEmail(string $email, bool $isUserCreation): void {
        $passwordResetToken = $this->userService->createPasswordResetToken($email);
        
        if ($passwordResetToken == null) {
            if (!$isUserCreation) return;
            $this->logger?->error("User for email '$email' was created but password reset token could not be created.");
            throw new UserFriendlyException('Es ist ein Fehler aufgetreten. Bitte versuchen Sie es zu neinem späteren Zeitpunkt nochmal oder wenden Sie sich an den Administrator.', 500);
        }
        
        $token = base64_encode($email . '|' . $passwordResetToken);
        $fullUserName = $this->userService->getFullUserNameByEmail($email) ?? $email;

        try {
            if ($isUserCreation) $this->sendPasswordActivationMail($email, $fullUserName, $token);
            else $this->sendPasswordResetMail($email, $fullUserName, $token);
        } catch (\Exception $e) {
            $this->logger?->error("Was not able to send password reset link to $email: " . $e->getMessage());
            throw new UserFriendlyException('Es ist ein Fehler aufgetreten. Bitte versuchen Sie es zu neinem späteren Zeitpunkt nochmal oder wenden Sie sich an den Administrator.', 500);
        }
    }

    public function isPasswordResetTokenValid(string $resetToken): bool {
        list($email, $token) = $this->parsePasswordResetToken($resetToken);
        return $this->userService->isPasswordResetTokenValid($email, $token);
    }

    public function setPasswordAndLogin(string $resetToken, string $password): string {
        list($email, $token) = $this->parsePasswordResetToken($resetToken);
        if (!$this->userService->isPasswordResetTokenValid($email, $token)) {
            throw new UserFriendlyException("Der Link ist nicht oder nicht mehr gültig.", 403);
        }

        $this->userService->setPassword($email, $password);
        return $this->login($email, $password);
    }

    private function parsePasswordResetToken($token): array {
        $parts = explode('|', base64_decode($token));
        if (count($parts) != 2) throw new \Exception('Invalid token format');
        return [$parts[0], $parts[1]];
    }

    private function sendPasswordActivationMail(string $email, string $fullUserName, string $token): void {
        $link = $this->appsettingsManager->get(self::APPSETTINGS_FRONTENDURL_KEY) . '/account/setpassword/' . urlencode($token);
        $greeting = $this->appsettingsManager->get(self::APPSETTINGS_EMAILGREETING_KEY);
        $subject = 'Neuer Benutzer wurde erstellt';
        $message = "Hallo $fullUserName,\n\nfür dich wurde ein neuer Benutzer erstellt. Über den nachfolgenden Link kannst du nun ein eigenes Passwort vergeben: $link\n\nWarst du das nicht? Ignoriere in diesem Fall diese Email einfach, das Konto wird nicht aktiv.\n\nViele Grüße,\n".$greeting;
        $this->mailService->sendMail($email, $subject, $message, false);
    }

    private function sendPasswordResetMail(string $email, string $fullUserName, string $token): void {
        $link = $this->appsettingsManager->get(self::APPSETTINGS_FRONTENDURL_KEY) . '/account/setpassword/' . urlencode($token);
        $greeting = $this->appsettingsManager->get(self::APPSETTINGS_EMAILGREETING_KEY);
        $subject = 'Neues Passwort angefordert';
        $message = "Hallo $fullUserName,\n\ndu hast ein neues Passwort angefordert. Bitte klicke den nachfolgenden Link oder kopiere ihn in die Adresszeile deines Browsers, um ein neues Passwort zu vergeben.\n\nLink zum Zurücksetzen des Passworts: $link\n\nHast du diese Passwort-Vergabe nicht angefordert, ignoriere diese Email einfach.\n\nViele Grüße,\n".$greeting;
        $this->mailService->sendMail($email, $subject, $message, false);
    }
}