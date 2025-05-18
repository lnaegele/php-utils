<?PHP
declare(strict_types=1);
namespace Jolutions\PhpUtils\Entity\Domain\Interfaces;

use DateTime;

interface ModificationAuditedEntityInterface extends CreationAuditedEntityInterface
{
    public function getLastModificationUserId(): ?int;
    public function getLastModificationTime(): ?DateTime;
    
    public function setLastModificationUserId(?int $lastModificationUserId): void;
    public function setLastModificationTime(?DateTime $lastModificationTime): void;
}