<?PHP
declare(strict_types=1);
namespace Jolutions\PhpUtils\Entity\Domain\Models;

use Jolutions\PhpUtils\Entity\Domain\Interfaces\EntityInterface;

abstract class Entity implements EntityInterface
{
    private int $id;

    public function getId(): int {
        return $this->id;
    }
    
    public function setId(int $id): void {
        $this->id = $id;
    }
}