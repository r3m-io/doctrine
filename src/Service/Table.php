<?php
namespace R3m\Io\Doctrine\Service;


use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\ORM\Exception\NotSupported;
use Doctrine\ORM\Mapping\Driver\AttributeReader;
use Doctrine\ORM\Query\Parameter;
use Entity\Role;
use ReflectionObject;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Tools\Pagination\Paginator;

use Host\Api\Workandtravel\World\Service\User as UserService;

use R3m\Io\App;
use R3m\Io\Module\Core;
use R3m\Io\Module\Database;
use R3m\Io\Module\File;
use R3m\Io\Module\Limit;
use R3m\Io\Module\Parse;

use Exception;

use Doctrine\ORM\NoResultException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\Query\QueryException;

use R3m\Io\Exception\AuthorizationException;
use R3m\Io\Exception\ObjectException;
use R3m\Io\Exception\FileWriteException;
use Repository\PermissionRepository;


class Table extends Main

{

    /**
     * @throws Exception
     */
    public static function all($object, $name, $environment=null): array
    {
        $name = str_replace('.', '-', $name);
        $environment = str_replace('.', '-', $environment);
        d($name);
        d($environment);
        try {
            $schema_manager = Database::schema_manager($object, $name, $environment);
        }
        catch(Exception $exception){
            Database::instance($object, $name, $environment);
            $schema_manager = Database::schema_manager($object, $name, $environment);
        }
        $tables = [];
        if($schema_manager){
            $tables = $schema_manager->listTableNames();
        }
        return $tables;
    }

    /**
     * @throws Exception
     */
    public static function truncate($object, $name, $environment=null, $options=null): bool
    {
        $options = Core::object($options);
        if($environment === null){
            $environment = $object->config('environment');
        } else {
            $environment = str_replace('.', '-', $environment);
        }
        $name = str_replace('.', '-', $name);
        if(!property_exists($options, 'table')){
            throw new Exception('table not set in options');
        }
        $connection = Database::connection($object, $name, $environment);
        $sanitized_table = preg_replace('/[^a-zA-Z0-9_]/', '', $options->table);
        $driver = Database::driver($object, $name, $environment);
        $reset = false;
        switch($driver){
            case 'pdo_mysql':
                $sql = 'TRUNCATE TABLE ' . $sanitized_table;
                break;
            case 'pdo_sqlite':
                $sql = 'DELETE FROM ' . $sanitized_table;
                $reset = 'DELETE FROM SQLITE_SEQUENCE WHERE name = "' . $sanitized_table . '"';
                break;
            default:
                throw new Exception('Driver not supported.');

        }
        if($connection){
            try {
                $stmt = $connection->prepare($sql);
                $result = $stmt->executeStatement();
                if($driver === 'pdo_sqlite' && $reset){
                    try {
                        $stmt = $connection->prepare($reset);
                        $result = $stmt->executeStatement();
                    }
                    catch(Exception $exception){
                    }
                }
                return true;
            }
            catch(Exception $exception){
            }
        }
        return false;
    }

    /**
     * @throws Exception
     */
    public static function delete($object, $name, $environment=null, $options=null): bool
    {
        $options = Core::object($options);
        if($environment === null){
            $environment = $object->config('environment');
        } else {
            $environment = str_replace('.', '-', $environment);
        }
        $name = str_replace('.', '-', $name);
        if(!property_exists($options, 'table')){
            throw new Exception('table not set in options');
        }
        try {
            $schema_manager = Database::schema_manager($object, $name, $environment);
        }
        catch(Exception $exception){
            Database::instance($object, $name, $environment);
            $schema_manager = Database::schema_manager($object, $name, $environment);
        }
        $tables = Table::all($object, $name, $environment);
        $sanitized_table = preg_replace('/[^a-zA-Z0-9_]/', '', $options->table);
        if(in_array($sanitized_table, $tables, true)){
            $schema_manager->dropTable($sanitized_table);
            return true;
        }
        return false;
    }

    /**
     * @throws Exception
     */
    public static function rename(App $object, $name, $environment=null, $options=[]): bool | string
    {
        $options = Core::object($options);
        $name = str_replace('.', '-', $name);
        $environment = str_replace('.', '-', $environment);
        if(!property_exists($options, 'table')){
            throw new Exception('table not set in options');
        }
        if(property_exists($options, 'rename')){
            $tables = Table::all($object, $name, $environment);
            $table = '';
            $rename = '';
            if($options->rename === true){
                //new table name _old_nr
                $table = $options->table;
                $rename = $table . '_old';
                $counter = 1;
                while(true){
                    if(
                        in_array(
                            $rename,
                            $tables,
                            true
                        ) === false
                    ){
                        break;
                    }
                    $rename = $table . '_old_' . $counter;
                    $counter++;
                    if(
                        $counter >= PHP_INT_MAX ||
                        $counter < 0
                    ){
                        throw new Exception('Out of range.');
                    }
                }
            }
            elseif(is_string($options->rename)){
                if(
                    in_array(
                        $options->rename,
                        $tables,
                        true
                    )
                ){
                    return false;
                }
                $rename = $options->rename;
                //new table name
            }
            $sanitized_table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
            $sanitized_rename = preg_replace('/[^a-zA-Z0-9_]/', '', $rename);
            // Construct the SQL query with the sanitized table names
            if(
                strlen($sanitized_table) >= 2 &&
                strlen($sanitized_rename) >= 2
            ){
                $driver = Database::driver($object, $name, $environment);
                switch($driver){
                    case 'pdo_mysql':
                        $sql = 'RENAME TABLE ' . $sanitized_table . ' TO ' . $sanitized_rename;
                        break;
                    case 'pdo_sqlite':
                        $sql = 'ALTER TABLE ' . $sanitized_table . ' RENAME TO ' . $sanitized_rename;
                        break;
                    default:
                        throw new Exception('Driver not supported.');
                }
                $connection = Database::connection($object, $name, $environment);
                if($connection){
                    try {
                        $stmt = $connection->prepare($sql);
                        $result = $stmt->executeStatement();
                    }
                    catch(Exception $exception){
                        ddd($exception);
                    }
                }
                return $sanitized_rename;
            }
        }
        return false;
    }
}