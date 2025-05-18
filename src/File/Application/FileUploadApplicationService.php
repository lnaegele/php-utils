<?PHP
declare(strict_types=1);
namespace Jolutions\PhpUtils\File\Application;

use Jolutions\PhpUtils\File\Domain\FileService;
use Jolutions\PhpUtils\UserFriendlyError\Application\UserFriendlyException;

class FileUploadApplicationService
{
    public function __construct(
        private FileService $fileService,
    ) {}    

    /**
     * Takes a fileName and base64 encoded file content and returns a fileToken that is used as reference to this file cache.
     * @param string $fileName
     * @param string $fileContent base64 encoded file content
     * @return string fileToken
     */
    public function upload(string $fileName, string $fileContent) : string
    {
        return $this->fileService->storeTempFile($fileName, base64_decode($fileContent, true));
    }

    public function cleanup(string $fileToken) : void
    {
        if (!$this->fileService->deleteTempFile($fileToken)) {
            throw new UserFriendlyException("Not found", 404);
        }
    }
}