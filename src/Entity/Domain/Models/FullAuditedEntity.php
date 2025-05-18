<?PHP
declare(strict_types=1);
namespace Jolutions\PhpUtils\Entity\Domain\Models;

use DateTime;
use Jolutions\PhpUtils\Entity\Domain\Interfaces\FullAuditedEntityInterface;

abstract class FullAuditedEntity extends ModificationAuditedEntity implements FullAuditedEntityInterface
{
    private bool $isDeleted;
    private ?int $deletionUserId;
    private ?DateTime $deletionTime;

    public function isDeleted(): bool {
        return $this->isDeleted;
    }

    public function getDeletionUserId(): ?int {
        return $this->deletionUserId;
    }

    public function getDeletionTime(): ?DateTime {
        return $this->deletionTime;
    }

    public function setDeleted(bool $isDeleted): void {
        $this->isDeleted = $isDeleted;
    }

    public function setDeletionUserId(?int $deletionUserId): void {
        $this->deletionUserId = $deletionUserId;
    }

    public function setDeletionTime(?DateTime $deletionTime): void {
        $this->deletionTime = $deletionTime;
    }
}