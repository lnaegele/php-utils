<?PHP
declare(strict_types=1);
namespace Jolutions\PhpUtils\Entity\Domain\Models;

use DateTime;
use Jolutions\PhpUtils\Entity\Domain\Interfaces\ModificationAuditedEntityInterface;

abstract class ModificationAuditedEntity extends CreationAuditedEntity implements ModificationAuditedEntityInterface
{
    private ?int $lastModificationUserId;
    private ?DateTime $lastModificationTime;

    public function getLastModificationUserId(): ?int {
        return $this->lastModificationUserId;
    }

    public function getLastModificationTime(): ?DateTime {
        return $this->lastModificationTime;
    }

    public function setLastModificationUserId(?int $lastModificationUserId): void {
        $this->lastModificationUserId = $lastModificationUserId;
    }

    public function setLastModificationTime(?DateTime $lastModificationTime): void {
        $this->lastModificationTime = $lastModificationTime;
    }
}