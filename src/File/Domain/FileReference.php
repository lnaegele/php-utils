<?PHP
declare(strict_types=1);
namespace Jolutions\PhpUtils\File\Domain;

use Jolutions\PhpUtils\Entity\Domain\Models\CreationAuditedEntity;

class FileReference extends CreationAuditedEntity
{
    public function __construct(
        public string $name,
        public string $fileToken,
        public string $uri,
        public bool $isTemp = false,
    ) {}
}