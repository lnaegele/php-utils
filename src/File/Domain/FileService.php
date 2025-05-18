<?PHP
declare(strict_types=1);
namespace Jolutions\PhpUtils\File\Domain;

use Jolutions\PhpUtils\Appsettings\AppsettingsManager;
use Jolutions\PhpUtils\File\Persistence\IFileReferenceRepository;
use Jolutions\PhpUtils\Guid\GuidGenerator;

class FileService
{
    private const APPSETTINGS_FILE_STORAGE_PATH = 'fileStoragePath';

    public function __construct(
        private GuidGenerator $guidGenerator,
        private IFileReferenceRepository $fileReferenceRepository,
        private AppsettingsManager $appsettingsManager,
    ) {}

    public function getFileReferenceForFile(int $id): ?FileReference {
        $fileReferences = $this->fileReferenceRepository->getAll(['id=:id', 'isTemp=:isTemp'], ['id' => $id, 'isTemp' => 0]);
        return count($fileReferences)==0 ? null : $fileReferences[0];
    }

    public function getFileReferenceForTempFile(string $fileToken): ?FileReference {
        $fileReferences = $this->fileReferenceRepository->getAll(['fileToken=:fileToken', 'isTemp=:isTemp'], ['fileToken' => $fileToken, 'isTemp' => 1]);
        return count($fileReferences)==0 ? null : $fileReferences[0];
    }

    public function storeFile(string $fileName, string $fileContent): int {
        return $this->store($fileName, $fileContent, false)->getId();
    }

    public function storeTempFile(string $fileName, string $fileContent): string {
        return $this->store($fileName, $fileContent, true)->fileToken;
    }    

    public function moveTempFileToFile(string $fileToken): ?int {
        $oldFileReference = $this->getFileReferenceForTempFile($fileToken);
        if ($oldFileReference==null) return null;

        $newFileReference = new FileReference($oldFileReference->name, $oldFileReference->fileToken, $oldFileReference->uri, false);
        $this->fileReferenceRepository->insert($newFileReference);
        $this->fileReferenceRepository->delete($oldFileReference->getId());

        return $newFileReference->getId();
    }
    
    public function loadFile(int $id): ?File {
        return $this->load($this->getFileReferenceForFile($id));
    }
    
    public function loadTempFile(string $fileToken): ?File {
        return $this->load($this->getFileReferenceForTempFile($fileToken));
    }
    
    public function deleteFile(int $id): bool {
        return $this->delete($this->getFileReferenceForFile($id));
    }
    
    public function deleteTempFile(string $fileToken): bool {
        return $this->delete($this->getFileReferenceForTempFile($fileToken));
    }

    private function store(string $fileName, string $fileContent, bool $isTemp): FileReference {
        $path = $this->appsettingsManager->getAppSettingsDirectory().DIRECTORY_SEPARATOR.$this->appsettingsManager->get(self::APPSETTINGS_FILE_STORAGE_PATH);
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        $fileToken = $this->guidGenerator->create();
        $uri = $path.DIRECTORY_SEPARATOR.$fileToken;

        $fh = fopen($uri, 'a');
        fwrite($fh, $fileContent);
        fclose($fh);

        $fileReference = new FileReference($fileName, $fileToken, $uri, $isTemp, );
        $this->fileReferenceRepository->insert($fileReference);

        return $fileReference;
    }

    private function load(?FileReference $fileReference): ?File {
        if ($fileReference==null) return null;
        $fileContent = file_get_contents($fileReference->uri);
        return new File($fileReference->name, $fileContent, $fileReference->getCreationUserId(), $fileReference->getCreationTime());
    }    
    
    private function delete(?FileReference $fileReference): bool {
        if ($fileReference==null) return false;
        $result = $this->fileReferenceRepository->delete($fileReference->getId());
        unlink($fileReference->uri);
        return $result;
    }
}