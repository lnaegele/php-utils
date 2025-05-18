<?PHP
declare(strict_types=1);
namespace Jolutions\PhpUtils\File\Domain;

use DateTime;

class File
{
    public function __construct(
        public string $name,
        public string $content,
        public ?int $creationUserId,
        public DateTime $creationTime,
    ) {}
}