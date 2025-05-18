<?PHP
declare(strict_types=1);
namespace Jolutions\PhpUtils\Entity\Domain\Interfaces;

use DateTime;

interface CreationAuditedEntityInterface extends EntityInterface
{
    public function getCreationUserId(): ?int;
    public function getCreationTime(): DateTime;
    
    public function setCreationUserId(?int $creationUserId): void;
    public function setCreationTime(DateTime $creationTime): void;
}