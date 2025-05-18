<?PHP
declare(strict_types=1);
namespace Jolutions\PhpUtils\File\Persistence;

use Jolutions\PhpUtils\File\Domain\FileReference;

interface IFileReferenceRepository
{
    /**
     * @param string[] $whereExpressions e.g. ["name LIKE %:nameVar%"]
     * @param object[] $whereParamValues e.g. ["nameVar" => "Peter"]
     * @return FileReference[]
     */
    public function getAll(array $whereExpressions=[], array $whereParamValues=[]);

    public function insert(FileReference $fileReference): void;

    public function delete(int $id): bool;
}