<?PHP
declare(strict_types=1);
namespace Jolutions\PhpUtils\Authentication\Domain;

interface AuthMailServiceInterface
{
    /**
     * @param string $email
     * @param string $fullUserName
     * @param string $token
     */
    public function sendAccountCreationMail(string $email, string $fullUserName, string $token): void;

    /**
     * @param string $email
     * @param string $fullUserName
     * @param string $token
     */
    public function sendEmailConfirmationMail(string $email, string $fullUserName, string $token): void;

    /**
     * @param string $email
     * @param string $fullUserName
     * @param string $token
     */
    public function sendPasswordResetMail(string $email, string $fullUserName, string $token): void;
}