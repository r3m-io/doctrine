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

    public static function all($object, $name, $environment=null): array
    {
        $name = str_replace('.', '-', $name);
        $environment = str_replace('.', '-', $environment);
        Database::instance($object, $name, $environment);
        $schema_manager = Database::schema_manager($object, $name, $environment);
        $tables = [];
        if($schema_manager){
            $tables = $schema_manager->listTableNames();
        }
        d($tables);
        return $tables;
    }

    /**
     * @throws Exception
     */
    public static function rename(App $object, $name, $environment=null, $options=[]): bool | string
    {
        $name = str_replace('.', '-', $name);
        $environment = str_replace('.', '-', $environment);
        if(!array_key_exists('table', $options)){
            throw new Exception('table not set in options');
        }
        if(
            array_key_exists('rename', $options)
        ){
            $tables = Table::all($object, $name, $environment);
            $table = '';
            $rename = '';
            d($options);
            if($options['rename'] === true){
                //new table name _old_nr
                $table = $options['table'];
                $rename = $table . '_old';
                d($table);
                d($rename);
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
            elseif(is_string($options['rename'])){
                if(
                    in_array(
                        $options['rename'],
                        $tables,
                        true
                    )
                ){
                    return false;
                }
                $rename = $options['rename'];
                //new table name
            }
            d('yes');
            $sanitized_table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
            $sanitized_rename = preg_replace('/[^a-zA-Z0-9_]/', '', $rename);
            d($sanitized_table);
            d($sanitized_rename);
            // Construct the SQL query with the sanitized table names
            if(
                strlen($sanitized_table) >= 2 &&
                strlen($sanitized_rename) >= 2
            ){
                $driver = Database::driver($object, $name, $environment);
                switch($driver){
                    case 'pdo_mysql':
                        $sql = "RENAME TABLE $sanitized_table TO $sanitized_rename";
                        break;
                    case 'pdo_sqlite':
                        $sql = "ALTER TABLE $sanitized_table RENAME TO $sanitized_rename";
                        break;
                    default:
                        throw new Exception('Driver not supported.');
                }
                $connection = Database::connection($object, $name, $environment);
                d($sql);
                if($connection){
                    try {
                        $stmt = $connection->prepare($sql);
                        $result = $stmt->executeStatement();
                        d($result);
                    }
                    catch(Exception $exception){
                        d($exception);
                    }

                }
                return $sanitized_rename;
            }
        }
        return false;
    }
}