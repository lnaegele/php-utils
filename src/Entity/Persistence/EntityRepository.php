<?PHP
declare(strict_types=1);
namespace Jolutions\PhpUtils\Entity\Persistence;

use DateTime;
use DateTimeZone;
use DI\Container;
use Exception;
use Jolutions\PhpUtils\Authentication\Domain\UserSession;
use Jolutions\PhpUtils\DateTime\DateTimeService;
use Jolutions\PhpUtils\Entity\Domain\Interfaces\CreationAuditedEntityInterface;
use Jolutions\PhpUtils\Entity\Domain\Interfaces\EntityInterface;
use Jolutions\PhpUtils\Entity\Domain\Interfaces\FullAuditedEntityInterface;
use Jolutions\PhpUtils\Entity\Domain\Interfaces\ModificationAuditedEntityInterface;
use PDO;

/**
 * @template T of EntityInterface
 */
abstract class EntityRepository
{
    private readonly bool $isCreationAudited;
    private readonly bool $isModificationAudited;
    private readonly bool $isFullAudited;

    /**
     * @param class-string<T> $className
     */
    function __construct(
        string $className,
        private string $tableName,
        private Container $container,
    ) {
        $this->isCreationAudited = is_subclass_of($className, CreationAuditedEntityInterface::class);
        $this->isModificationAudited = is_subclass_of($className, ModificationAuditedEntityInterface::class);
        $this->isFullAudited = is_subclass_of($className, FullAuditedEntityInterface::class);
    }

    /**
     * @param string[] $whereExpressions e.g. ["name LIKE %:nameVar%"]
     * @param array<string, mixed> $whereParamValues e.g. ["nameVar" => "Peter"]
     * @param string[] $orderBy
     * @param bool $disableSoftDeletionFilter
     * @return T[]
     */
    public final function getAll(array $whereExpressions=[], array $whereParamValues=[], array $orderBy=[], bool $disableSoftDeletionFilter=false): array {
        // soft deletion
        if ($this->isFullAudited && !$disableSoftDeletionFilter) {
            $whereExpressions[] = "isDeleted=0";
        }

        $where = count($whereExpressions)==0 ? "" : (" WHERE (" . implode(") AND (", $whereExpressions) . ")");
        $order = count($orderBy)==0 ? "" : (" ORDER BY " . implode(", ", $orderBy));
        $statement = $this->container->get(PDO::class)->prepare("SELECT * FROM `$this->tableName`$where$order;");
        $statement->execute($whereParamValues);
        
        $result = [];
        foreach ($statement->fetchAll() as $row) {
            $result[] = $this->createObjectFromRow($row);
        }

        $statement->closeCursor();
        return $result;
    }

    /**
     * @param int $id
     * @param string[] $whereExpressions e.g. ["name LIKE %:nameVar%"]
     * @param array<string, mixed> $whereParamValues e.g. ["nameVar" => "Peter"]
     * @param bool $disableSoftDeletionFilter
     * @return ?T
     */
    public final function getByIdOrNull(int $id, array $whereExpressions=[], array $whereParamValues=[], bool $disableSoftDeletionFilter=false): ?EntityInterface {
        $whereExpressions[] = "id = $id";
    
        // soft deletion
        if ($this->isFullAudited && !$disableSoftDeletionFilter) {
            $whereExpressions[] = "isDeleted=0";
        }

        $where = " WHERE (" . implode(") AND (", $whereExpressions) . ")";
        $statement = $this->container->get(PDO::class)->prepare("SELECT * FROM `$this->tableName`$where;");
        $statement->execute($whereParamValues);  
        $row = $statement->fetch();
        $statement->closeCursor();
        if ($row !== false) {
            return $this->createObjectFromRow($row);
        }

        return null;
    }

    /**
     * @param T $object
     */
    public final function insert(EntityInterface $object): void {
        $reservedFieldNames = ["id"];
        $fieldNames = [];
        $valueExpressions = [];
        $entityDetails = [];
        
        // creation audited
        if ($this->isCreationAudited) {
            $userSession = $this->container->get(UserSession::class);
            $reservedFieldNames = array_merge($reservedFieldNames, ["creationUser", "creationTime"]);
            $fieldNames[] = 'creationUser';
            $fieldNames[] = 'creationTime';
            $creationUserId = $userSession->getUserId();
            $now = $this->dateTimeToDb(new DateTime());
            $valueExpressions[] = $creationUserId ?? 'NULL';
            $valueExpressions[] = "'".$now."'";
            $entityDetails['creationUser'] = $creationUserId;
            $entityDetails['creationTime'] = $now;
        }

        // modification audited
        if ($this->isModificationAudited) {
            $reservedFieldNames = array_merge($reservedFieldNames, ["lastModificationUser", "lastModificationTime"]);
            $fieldNames[] = 'lastModificationUser';
            $fieldNames[] = 'lastModificationTime';
            $valueExpressions[] = 'NULL';
            $valueExpressions[] = 'NULL';
        }

        // full audited
        if ($this->isFullAudited) {
            $reservedFieldNames = array_merge($reservedFieldNames, ["isDeleted", "deletionUser", "deletionTime"]);
            $fieldNames[] = 'isDeleted';
            $fieldNames[] = 'deletionUser';
            $fieldNames[] = 'deletionTime';
            $valueExpressions[] = '0';
            $valueExpressions[] = 'NULL';
            $valueExpressions[] = 'NULL';
        }
        
        $cnt = 1;
        $params = [];
        foreach ($this->createValuesFromObject($object) as $fieldName => $value) {
            if (in_array($fieldName, $reservedFieldNames)) throw new Exception("Field name '$fieldName' is reserved and can not be used.");
            $fieldNames[] = $fieldName;
            $valueExpressions[] = ":p$cnt";
            $params["p$cnt"] = $value;
            $cnt++; 
        }

        $_fieldNames = implode(", ", $fieldNames);
        $_valueExpressions = implode(", ", $valueExpressions);
        $insertSql = "INSERT INTO `$this->tableName` ($_fieldNames) VALUES ($_valueExpressions);";

        $pdo = $this->container->get(PDO::class);
        $statement = $pdo->prepare($insertSql);
        $statement->execute($params);

        $_id = $pdo->lastInsertId();
        $statement->closeCursor();

        $id = intval($_id);
        if ($id!=$_id) throw new Exception("Auto increment number can not be presented by int.");

        $entityDetails["id"] = $id;
        $this->injectEntityDetails($object, $entityDetails);
    }

    /**
     * @param T $object
     */
    public final function update(EntityInterface $object): bool {
        $reservedFieldNames = ["id"];
        $setExpressions = [];
        $whereExpressions = ["id = ".$object->getId()];
        $entityDetails = [];

        // creation audited
        if ($this->isCreationAudited) {
            $reservedFieldNames = array_merge($reservedFieldNames, ["creationUser", "creationTime"]);
        }
        
        // modification audited
        if ($this->isModificationAudited) {
            $userSession = $this->container->get(UserSession::class);
            $reservedFieldNames = array_merge($reservedFieldNames, ["lastModificationUser", "lastModificationTime"]);
            $lastModificationUserId = $userSession->getUserId();
            $now = $this->dateTimeToDb(new DateTime());
            $setExpressions[] = "lastModificationUser = ".($lastModificationUserId ?? "NULL");
            $setExpressions[] = "lastModificationTime = '".$now."'";
            $entityDetails['lastModificationUser'] = $lastModificationUserId;
            $entityDetails['lastModificationTime'] = $now;
        }

        // full audited
        if ($this->isFullAudited) {
            $reservedFieldNames = array_merge($reservedFieldNames, ["isDeleted", "deletionUser", "deletionTime"]);
            $setExpressions[] = "isDeleted = 0";
            $setExpressions[] = "deletionUser = NULL";
            $setExpressions[] = "deletionTime = NULL";
            $whereExpressions[] = "isDeleted=0";
        }
        
        $cnt = 1;
        $params = [];
        foreach ($this->createValuesFromObject($object) as $fieldName => $value) {
            if (in_array($fieldName, $reservedFieldNames)) throw new Exception("Field name '$fieldName' is reserved and can not be used.");
            $setExpressions[] = "$fieldName = :p$cnt";
            $params["p$cnt"] = $value;
            $cnt++; 
        }
        
        $where = " WHERE " . implode(" AND ", $whereExpressions);
        $_setExpressions = implode(", ", $setExpressions);
        $statement = $this->container->get(PDO::class)->prepare("UPDATE `$this->tableName` SET $_setExpressions$where;");
        $statement->execute($params);
        $result = $statement->rowCount()>0;
        $statement->closeCursor();

        $this->injectEntityDetails($object, $entityDetails);

        return $result;
    }

    public final function delete(int $id): bool {
        $pdo = $this->container->get(PDO::class);
        $statement = null;
        if ($this->isFullAudited) {
            $userSession = $this->container->get(UserSession::class);
            $statement = $pdo->prepare("UPDATE `$this->tableName` SET isDeleted = 1, deletionUser = :deletionUser, deletionTime = :deletionTime WHERE id = :id AND isDeleted=0;");
            $statement->execute([
                "id" => $id,
                "deletionUser" => $userSession->getUserId(),
                "deletionTime" => $this->dateTimeToDb(new DateTime())
            ]);
        } else {
            $statement = $pdo->prepare("DELETE FROM `$this->tableName` WHERE id = :id");
            $statement->execute(["id" => $id]);
        }

        $result = $statement->rowCount()>0;
        $statement->closeCursor();
        return $result;
    }

    public final function undelete(int $id): bool {
        if (!$this->isFullAudited) return false;

        $setExpressions = ["isDeleted = 0", "deletionUser = null", "deletionTime = null"];
        $params = ["id" => $id];

        if ($this->isModificationAudited) {
            $userSession = $this->container->get(UserSession::class);
            $setExpressions[] = "lastModificationUser = :lastModificationUser";
            $setExpressions[] = "lastModificationTime = :lastModificationTime";
            $params["lastModificationUser"] = $userSession->getUserId();
            $params["lastModificationTime"] = $this->dateTimeToDb(new DateTime());
        }

        $_setExpressions = implode(", ", $setExpressions);
        $statement = $this->container->get(PDO::class)->prepare("UPDATE `$this->tableName` SET $_setExpressions WHERE id = :id AND isDeleted=1;");
        $statement->execute($params);
        $result = $statement->rowCount()>0;
        $statement->closeCursor();
        return $result;
    }

    /**
     * @param array<string, mixed> $values fieldName => fieldValue
     * @return T
     */
    protected abstract function createObjectFromValues(array $values): EntityInterface;

    /**
     * @param T $object
     * @return array<string, mixed> fieldName => fieldValue
     */
    protected abstract function createValuesFromObject(EntityInterface $object): array;

    protected function dateTimeFromDb(?string $dateTime): ?DateTime {
        // Database type "DateTime" does not know about time zones. It just stores date and time. We always interpret it as UTC.
        if ($dateTime==null) return null;
        return DateTime::createFromFormat('Y-m-d H:i:s', $dateTime, new DateTimeZone("UTC"));
    }

    protected function dateTimeToDb(?DateTime $dateTime): ?string {
        // Database type "DateTime" does not know about time zones. It just stores date and time. We always interpret it as UTC.
        if ($dateTime==null) return null;
        $copy = clone $dateTime;
        $copy->setTimezone(new DateTimeZone("UTC"));
        return $copy->format('Y-m-d H:i:s');
    }

    /**
     * @param array<string, mixed> $row fieldName => fieldValue
     * @return T
     */
    private function createObjectFromRow(array $row): EntityInterface {
        $object = $this->createObjectFromValues($row);
        $this->injectEntityDetails($object, $row);
        return $object;
    }

    /**
     * @param T $object
     * @param array<string, mixed> $entityDetails fieldName => fieldValue
     */
    private function injectEntityDetails(EntityInterface $object, array $entityDetails): void {
        if (array_key_exists('id', $entityDetails)) $object->setId($entityDetails['id']);

        if ($this->isCreationAudited) {
            /** @var CreationAuditedEntityInterface $object */
            if (array_key_exists('creationUser', $entityDetails)) $object->setCreationUserId($entityDetails['creationUser']);
            if (array_key_exists('creationTime', $entityDetails)) $object->setCreationTime($this->dateTimeFromDb($entityDetails['creationTime']));
        }

        if ($this->isModificationAudited) {
            /** @var ModificationAuditedEntityInterface $object */
            if (array_key_exists('lastModificationUser', $entityDetails)) $object->setLastModificationUserId($entityDetails['lastModificationUser']);
            if (array_key_exists('lastModificationTime', $entityDetails)) $object->setLastModificationTime($this->dateTimeFromDb($entityDetails['lastModificationTime']));
        }

        if ($this->isFullAudited) {
            /** @var FullAuditedEntityInterface $object */
            if (array_key_exists('isDeleted', $entityDetails)) $object->setDeleted($entityDetails['isDeleted'] == 1);
            if (array_key_exists('deletionUser', $entityDetails)) $object->setDeletionUserId($entityDetails['deletionUser']);
            if (array_key_exists('deletionTime', $entityDetails)) $object->setDeletionTime($this->dateTimeFromDb($entityDetails['deletionTime']));
        }
    }
}