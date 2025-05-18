<?PHP
declare(strict_types=1);
namespace Jolutions\PhpUtils\Entity\Domain\Interfaces;

interface EntityInterface
{
    public function getId(): int;
    
    public function setId(int $id): void;
}