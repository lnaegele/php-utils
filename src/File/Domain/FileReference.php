<?PHP
declare(strict_types=1);
namespace Jolutions\PhpUtils\File\Domain;

use Jolutions\PhpUtils\Entity\Domain\Models\CreationAuditedEntity;

class FileReference extends CreationAuditedEntity
{
    /**
     * @param string $uri location of the file relative to the file storage directory (appsetting "fileStoragePath"),
     *      so the database stays valid if the application is moved. Files stored before version 2.0 have an absolute path.
     *      Use FileService::getFilePath() to access the file.
     */
    public function __construct(
        public string $name,
        public string $fileToken,
        public string $uri,
        public bool $isTemp = false,
    ) {}
}