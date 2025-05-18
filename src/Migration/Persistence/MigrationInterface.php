<?php
declare(strict_types=1);
namespace Jolutions\PhpUtils\Migration\Persistence;

use PDO;

interface MigrationInterface
{
    public function up(PDO $pdo): void;
}