<?PHP
declare(strict_types=1);
namespace Jolutions\PhpUtils\Authentication\Application;

use Jolutions\PhpUtils\Authentication\Domain\AuthMailServiceInterface;
use Jolutions\PhpUtils\Authentication\Domain\UserSession;
use Jolutions\PhpUtils\Authentication\Domain\UserTrait;
use Jolutions\PhpUtils\Authentication\Persistence\TokenRepositoryInterface;
use Jolutions\PhpUtils\Authentication\Persistence\UserRepositoryInterface;
use Jolutions\PhpUtils\Guid\GuidGenerator;
use Jolutions\PhpUtils\UserFriendlyError\Application\UserFriendlyException;
use Psr\Log\LoggerInterface;

class AuthApplicationService
{
    public function __construct(
        private UserSession $userSession,
        private UserRepositoryInterface $userRepository,
        private TokenRepositoryInterface $tokenRepository,
        private GuidGenerator $guidGenerator,
        private AuthMailServiceInterface $mailService,
        private LoggerInterface $logger,
    ) {}

    public function login(string $email, string $password) : string
    {
        $user = $this->userRepository->getUserByEmailOrNull($email);
        if ($user==null) throw new UserFriendlyException("Falsche Login-Daten.", 401);

        // noch kein Passwort vergeben
        if ($user->passwordHash==null) {
            $this->createPasswordResetTokenAndSendEmail($user);
            throw new UserFriendlyException("Email-Bestätigung ist noch ausstehend. Eine Email wurde an die hinterlegte Email-Adresse versandt.", 403);
        }

        // passwort korrekt?
        if (!password_verify($password, $user->passwordHash)) throw new UserFriendlyException("Falsche Login-Daten.", 401);

        // Ggf. ein vorhandenes Passwort-Reset-Token oder einen vorhandenen Email-Changerequest invalidieren
        if ($user->confirmationToken!=null) {
            $user->replacementEmail = null;
            $user->confirmationToken = null;
            $user->confirmationTokenValidUntil = null;
            $this->userRepository->updateUser($user);
        }

        // Email-Confirmation ausstehend?
        if (!$user->isEmailConfirmed) {
            $this->createEmailConfirmationTokenAndSendEmail($user);
            throw new UserFriendlyException("Email-Bestätigung ist noch ausstehend. Eine Email wurde an die hinterlegte Email-Adresse versandt.", 403);
        }

        return $this->tokenRepository->createToken($user->getId());
    }

    public function logout() : void
    {
        $authToken = $this->userSession->getAuthToken();
        if ($authToken != null) {
            $this->tokenRepository->invalidateToken($authToken);
        }
    }

    public function triggerUpdateEmail(string $newEmail): void {
        $userId = $this->userSession->getUserId();
        if ($userId==null) throw new UserFriendlyException("Nicht eingeloggt.", 403);

        $user = $this->userRepository->getUserByIdOrNull($userId);
        if ($user==null)  throw new UserFriendlyException("Nicht eingeloggt.", 403);
        
        if ($user->email==$newEmail) return;

        // check new email is still free
        if ($this->userRepository->getUserByEmailOrNull($newEmail)!=null) throw new UserFriendlyException("Die Emailadresse ist bereits vergeben.", 400);

        // create token
        $confirmationToken = $this->guidGenerator->create();
        $user->replacementEmail = $newEmail;
        $user->confirmationToken = $confirmationToken;
        $user->confirmationTokenValidUntil =  (new \DateTime('now'))->modify('+1 day');
        $this->userRepository->updateUser($user);

        $token = self::createToken($user->email, $confirmationToken);
        try {
            $this->mailService->sendEmailConfirmationMail($newEmail, $user->getFullUserName(), $token);
        } catch (\Exception $e) {
            $this->logger?->error("Was not able to send email confirmation link to $newEmail: " . $e->getMessage());
            throw new UserFriendlyException('Es ist ein Fehler aufgetreten. Bitte versuchen Sie es zu neinem späteren Zeitpunkt nochmal oder wenden Sie sich an den Administrator.', 500);
        }
    }

    public function completeEmailConfirmation(string $confirmationToken): void {
        $user = $this->getUserIfConfirmationTokenValid($confirmationToken);
        if ($user==null) throw new UserFriendlyException("Der Link ist nicht oder nicht mehr gültig.", 403);

        $user->isEmailConfirmed = true;
        $user->confirmationToken = null;
        $user->confirmationTokenValidUntil = null;
        try {
            if ($user->replacementEmail!=null) {
                $replacementEmail = $user->replacementEmail;
                $user->replacementEmail = null;
                if (count($this->userRepository->getUserByEmailOrNull($replacementEmail))) throw new UserFriendlyException("Die Emailadresse ist bereits vergeben.", 400);
                $user->email = $replacementEmail;
            }
        } finally {
            $this->userRepository->updateUser($user);
        }
    }

    public function triggerResetPassword(string $email): void {
        $user = $this->userRepository->getUserByEmailOrNull($email);
        if ($user==null) return; // do not indicate to externals about this email does not exist
        $this->createPasswordResetTokenAndSendEmail($user);
    }

    public function isPasswordResetTokenValid(string $resetToken): bool {
        return $this->getUserIfConfirmationTokenValid($resetToken)!=null;
    }

    public function completePasswordResetAndLogin(string $resetToken, string $password): string {
        $user = $this->getUserIfConfirmationTokenValid($resetToken);
        if ($user==null || $user->replacementEmail!=null) throw new UserFriendlyException("Der Link ist nicht oder nicht mehr gültig.", 403);

        $user->isEmailConfirmed = true;
        $user->passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $user->confirmationToken = null;
        $user->confirmationTokenValidUntil = null;
        $this->userRepository->updateUser($user);

        return $this->login($user->email, $password);
    }

    private function getUserIfConfirmationTokenValid(string $confirmationToken): ?UserTrait {
        list($email, $token) = $this->parseToken($confirmationToken);        
        $user = $this->userRepository->getUserByEmailOrNull($email);
        return $user!=null
            && $user->confirmationToken!=null && $user->confirmationToken == $token
            && $user->confirmationTokenValidUntil!=null && $user->confirmationTokenValidUntil >= new \DateTime('now')
            ? $user : null;
    }

    private function createEmailConfirmationTokenAndSendEmail(UserTrait $user): void {
        $confirmationToken = $this->guidGenerator->create();
        $user->replacementEmail = null;
        $user->confirmationToken = $confirmationToken;
        $user->confirmationTokenValidUntil =  (new \DateTime('now'))->modify('+1 day');
        $this->userRepository->updateUser($user);

        $token = self::createToken($user->email, $confirmationToken);
        try {
            $this->mailService->sendEmailConfirmationMail($user->email, $user->getFullUserName(), $token);
        } catch (\Exception $e) {
            $this->logger?->error("Was not able to send email confirmation link to $user->email: " . $e->getMessage());
            throw new UserFriendlyException('Es ist ein Fehler aufgetreten. Bitte versuchen Sie es zu neinem späteren Zeitpunkt nochmal oder wenden Sie sich an den Administrator.', 500);
        }
    }

    private function createPasswordResetTokenAndSendEmail(UserTrait $user): void {
        $passwordResetToken = $this->guidGenerator->create();
        $user->confirmationToken = $passwordResetToken;
        $user->confirmationTokenValidUntil = (new \DateTime('now'))->modify('+1 day');
        $this->userRepository->updateUser($user);
        
        $token = self::createToken($user->email, $passwordResetToken);        
        try {
            $this->mailService->sendPasswordResetMail($user->email, $user->getFullUserName(), $token);
        } catch (\Exception $e) {
            $this->logger?->error("Was not able to send password reset link to $user->email: " . $e->getMessage());
            throw new UserFriendlyException('Es ist ein Fehler aufgetreten. Bitte versuchen Sie es zu neinem späteren Zeitpunkt nochmal oder wenden Sie sich an den Administrator.', 500);
        }
    }

    private static function createToken(string $email, string $code): string {
        return base64_encode($email . '|' . $code);
    }

    private function parseToken($token): array {
        $parts = explode('|', base64_decode($token));
        if (count($parts) != 2) throw new \Exception('Invalid token format');
        return [$parts[0], $parts[1]];
    }
}