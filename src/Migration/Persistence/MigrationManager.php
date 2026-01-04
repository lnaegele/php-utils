<?php
declare(strict_types=1);
namespace Jolutions\PhpUtils\Migration\Persistence;

use DateTime;
use DI\Container;
use Exception;
use Jolutions\PhpUtils\DateTime\DateTimeService;
use PDO;
use Psr\Log\LoggerInterface;
use ReflectionClass;

class MigrationManager
{    
    /** @var string[] $backgroundJobClasses */
    private static array $migrationClasses = [];

    public function __construct(
        private Container $container,
    ) {}

    public function registerMigration(string $migrationClass): void {
        $id = (new ReflectionClass($migrationClass))->getShortName();
        if (array_key_exists($id, self::$migrationClasses)) throw new Exception("Duplicate migration name '$id'.");
        self::$migrationClasses[$id] = $migrationClass;
    }

    public function run(): void {
        /** @var LoggerInterface $logger */
        $logger = $this->container->has(LoggerInterface::class) ? $this->container->get(LoggerInterface::class) : null;
        $pdo = $this->container->get(PDO::class);

        $this->createSystemTableIfNotExists($pdo);
        
        foreach (self::$migrationClasses as $id => $migrationClass) {
            //$pdo->beginTransaction();
            try {
                if ($this->isMigrationPresent($id, $pdo)) continue;

                /** @var MigrationInterface $migration */
                $migration = $this->container->get($migrationClass);
                
                $migration->up($pdo);
                if ($logger != null) $logger->info("Successfully run migration '$id'.");
                $this->setMigrationPresent($id, $pdo);
                //$pdo->commit();
            } catch (Exception $e) {
                if ($logger != null) $logger->error("Unexpected error while executing migration '$id'.", ["exception" => $e]);
                //$pdo->rollBack();
                break; // stop execution
            }
        }
    }

    private function createSystemTableIfNotExists(PDO $pdo): void {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS `sys_database_migrations` (
                `id` VARCHAR(500) NOT NULL COLLATE 'utf8mb4_general_ci',
                `creationTime` DATETIME NOT NULL,
                PRIMARY KEY (`id`) USING BTREE
            )
            COLLATE='utf8mb4_general_ci'
            ENGINE=InnoDB;"
        );
    }

    private function isMigrationPresent(string $id, PDO $pdo): bool {
        $statement = $pdo->prepare("SELECT * FROM sys_database_migrations WHERE id = :id;");
        $statement->execute(array("id" => $id));
        $row = $statement->fetch();
        $statement->closeCursor();
        return $row !== false;
    }

    private function setMigrationPresent(string $id, PDO $pdo): void {        
        $statement = $pdo->prepare("INSERT INTO sys_database_migrations (id, creationTime) VALUES (:id, :creationTime);");
        $statement->execute(array(
            "id" => $id,
            "creationTime" => (new DateTime())->format('Y-m-d H:i:s')
        ));
        $statement->closeCursor();
    }
}