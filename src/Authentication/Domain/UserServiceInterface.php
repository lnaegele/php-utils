<?PHP
declare(strict_types=1);
namespace Jolutions\PhpUtils\Authentication\Domain;

interface UserServiceInterface
{
    /**
     * @param string $email
     * @param string $password
     * @return ?int user id, or null if invalid credentials
     */
    public function getUserIdByCredentials(string $email, string $password): ?int;

    /**
     * @param string $email
     * @return bool whether the user has valid password or must first set a password (and confirm email address), or null if user does not exist
     */
    public function isPasswordExisting(string $email): ?bool;

    /**
     * @param string $email
     * @return ?string full user name, or null if invalid email
     */
    public function getFullUserNameByEmail(string $email): ?string;

    /**
     * @param string $email
     * @return string password reset token, or null if user does not exist
     */
    public function createPasswordResetToken(string $email): ?string;

    /**
     * @param string $email
     * @param string $resetToken
     * @return bool
     */
    public function isPasswordResetTokenValid(string $email, string $resetToken): bool;

    /**
     * @param string $email
     * @param string $password
     */
    public function setPassword(string $email, string $password): void;
}