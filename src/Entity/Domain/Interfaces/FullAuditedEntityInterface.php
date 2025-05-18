<?PHP
declare(strict_types=1);
namespace Jolutions\PhpUtils\Entity\Domain\Interfaces;

use DateTime;

interface FullAuditedEntityInterface extends ModificationAuditedEntityInterface
{
    public function isDeleted(): bool;
    public function getDeletionUserId(): ?int;
    public function getDeletionTime(): ?DateTime;
    
    public function setDeleted(bool $isDeleted): void;
    public function setDeletionUserId(?int $deletionUserId): void;
    public function setDeletionTime(?DateTime $deletionTime): void;
}