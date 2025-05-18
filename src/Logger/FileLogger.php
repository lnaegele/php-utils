<?PHP
declare(strict_types=1);
namespace Jolutions\PhpUtils\Logger;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class FileLogger extends Logger
{
    public function __construct(
        string $name,
        private string $filePath,
    ) {
        parent::__construct($name);
        parent::pushHandler(new StreamHandler($filePath, 100));
    }

    public function getFilePath(): string {
        return $this->filePath;
    }
}