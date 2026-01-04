<?php
declare(strict_types=1);
namespace Jolutions\PhpUtils\BackgroundJob;

use Cron\CronExpression;
use DateTime;
use DI\Container;
use Exception;
use PDO;
use Psr\Log\LoggerInterface;

class BackgroundJobManager
{
    /** @var string[] $backgroundJobClasses */
    private static array $backgroundJobClasses = [];

    public function __construct(
        private Container $container,
    ) {}

    public function registerJob(string $cronExpression, string $backgroundJobClass): void {
        if (array_key_exists($backgroundJobClass, self::$backgroundJobClasses)) throw new Exception("Duplicate background job '$backgroundJobClass'.");
        self::$backgroundJobClasses[$backgroundJobClass] = $cronExpression;
    }

    public function run(): void {
        /** @var LoggerInterface $logger */
        $logger = $this->container->has(LoggerInterface::class) ? $this->container->get(LoggerInterface::class) : null;
        $pdo = $this->container->get(PDO::class);
        
        $this->createSystemTableIfNotExists($pdo);

        foreach (self::$backgroundJobClasses as  $backgroundJobClass => $cronExpression) {
            /** @var string $backgroundJobClass*/
            $time_pre = microtime(true);
            try {
                if (!$this->isLockSuccessfulForDueBackgroundjob($backgroundJobClass, $cronExpression, $pdo)) continue;
                
                /** @var BackgroundJobInterface $backgroundJob */
                $backgroundJob = $this->container->get($backgroundJobClass);
                $backgroundJob->execute();
                
                if ($logger != null) $logger->info("Successfully run background job '$backgroundJobClass'.");
                $this->freeBackgroundjobLock($backgroundJobClass, microtime(true) - $time_pre, false, $pdo);
            } catch (Exception $e) {
                if ($this->container->has(LoggerInterface::class)) $this->container->get(LoggerInterface::class)->error("Unexpected error while executing background job '$backgroundJobClass'.", ["exception" => $e]);
                $this->freeBackgroundjobLock($backgroundJobClass, microtime(true) - $time_pre, true, $pdo);
            }
        }
    }

    private function createSystemTableIfNotExists(PDO $pdo): void {
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS `sys_backgroundjob_runs` (
                `id` VARCHAR(500) NOT NULL COLLATE 'utf8mb4_general_ci',
                `lastExecutionTime` DATETIME NOT NULL,
                `executionDuration` BIGINT(20) UNSIGNED NOT NULL,
                PRIMARY KEY (`id`) USING BTREE,
                INDEX `lastExecutionTime` (`lastExecutionTime`)
            )
            COLLATE='utf8mb4_general_ci'
            ENGINE=InnoDB;"
        );
        if (count($pdo->query("SHOW COLUMNS FROM `sys_backgroundjob_runs` WHERE Field = 'isRunning';")->fetchAll())==0) {
            $pdo->exec(
                "ALTER TABLE `sys_backgroundjob_runs`
                ADD COLUMN `isRunning` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' AFTER `id`,
                CHANGE COLUMN `executionDuration` `lastExecutionDuration` BIGINT(20) UNSIGNED NULL,
                ADD COLUMN `lastExecutionFailure` TINYINT(1) UNSIGNED NULL DEFAULT 0;"
            );
        }
    }

    private function isLockSuccessfulForDueBackgroundjob(string $id, string $cronExpression, PDO $pdo): bool {
        $cron = new CronExpression($cronExpression);
        $previousRunDate = $cron->getPreviousRunDate();

        // Try to insert new db row if none is existing so far
        $statement = $pdo->prepare("INSERT IGNORE INTO sys_backgroundjob_runs (id, isRunning, lastExecutionTime, lastExecutionDuration, lastExecutionFailure) VALUES (:id, 0, '0000-00-00 00:00:00', NULL, NULL);");
        $statement->execute(array("id" => $id));
        $statement->closeCursor();

        // Get lock
        $statement = $pdo->prepare("UPDATE sys_backgroundjob_runs SET isRunning=1, lastExecutionTime=:lastExecutionTime, lastExecutionDuration=NULL, lastExecutionFailure=NULL WHERE id = :id AND isRunning=0 AND lastExecutionTime < :previousRunTime;");
        $statement->execute(array(
            "id" => $id,
            "lastExecutionTime" => (new DateTime())->format('Y-m-d H:i:s'),
            "previousRunTime" => $previousRunDate->format('Y-m-d H:i:s')
        ));
        $isRowInserted = $statement->rowCount()>0;
        $statement->closeCursor();
        return $isRowInserted;
    }

    private function freeBackgroundjobLock(string $id, float $executionDuration, bool $executionFailure, PDO $pdo): void {
        $statement = $pdo->prepare("UPDATE sys_backgroundjob_runs SET isRunning=0, lastExecutionDuration=:lastExecutionDuration, lastExecutionFailure=:lastExecutionFailure WHERE id=:id;");
        $statement->execute(array(
            "id" => $id,
            "lastExecutionDuration" => $executionDuration,
            "lastExecutionFailure" => $executionFailure ? '1' : '0'
        ));
        $statement->closeCursor();
    }
}