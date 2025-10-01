<?PHP
declare(strict_types=1);
namespace Jolutions\PhpUtils\Entity\Domain\Models;

use Jolutions\PhpUtils\Entity\Domain\Interfaces\EntityInterface;

/**
 * @template T of EntityInterface
 */
class PaginatedList
{
    /**
     * @param array<int,T> $items
     */
    function __construct(
        public readonly int $totalNumber,
        public readonly int $page,
        public readonly int $pageSize,
        public readonly array $items,
    ) {}
}