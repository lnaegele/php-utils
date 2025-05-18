<?PHP
declare(strict_types=1);
namespace Jolutions\PhpUtils\Entity\Domain\Models;

use DateTime;
use Jolutions\PhpUtils\Entity\Domain\Interfaces\CreationAuditedEntityInterface;

abstract class CreationAuditedEntity extends Entity implements CreationAuditedEntityInterface
{
    private ?int $creationUserId;
    private DateTime $creationTime;

    public function getCreationUserId(): ?int {
        return $this->creationUserId;
    }

    public function getCreationTime(): DateTime {
        return $this->creationTime;
    }

    public function setCreationUserId(?int $creationUserId): void {
        $this->creationUserId = $creationUserId;
    }

    public function setCreationTime(DateTime $creationTime): void {
        $this->creationTime = $creationTime;
    }
}