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

    public static function all($object, $name, $environment=null){
        Database::instance($object, $name, $environment);
        $schema_manager = Database::schema_manager($object, $name, $environment);
        $tables = [];
        if($schema_manager){
            $tables = $schema_manager->listTableNames();
        }
        d($tables);
        return $tables;
    }

    public static function has(App $object, $class, $role, $node, $options=[]): bool
    {
        if(array_key_exists('environment', $options)){
            $config = $options['environment'];
            Database::instance($object, $config->name, $config->environment);
            $tables = Database::tables($object, $config->name, $config->environment);
            if(in_array($node->table, $tables)){
                return true;
            }
        }
        return false;
    }


    /**
     * @throws Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public static function create(App $object, $flags, $options): array
    {
        if(!property_exists($options, 'platform')){
            throw new Exception('Option, Platform not set...');
        }
        if(!property_exists($options, 'url')){
            throw new Exception('Option, Url not set...');
        }
        $read = $object->data_read($options->url);
        if($read){
            $schema = new Schema();
            $schema_table = $schema->createTable($read->get('Schema.table'));
            $columns = $read->get('Schema.columns');
            foreach($columns as $column_name => $column){
                if(property_exists($column, 'type')){
                    if(property_exists($column, 'options')){
                        $schema_options = (array) $column->options;
                        if(array_key_exists('nullable', $schema_options)){
                            $schema_options['notnull'] = !$schema_options['nullable'];
                            unset($schema_options['nullable']);
                        }
                        if(!empty($schema_options)) {
                            $schema_table->addColumn($column_name, $column->type, $schema_options);
                        }
                    } else {
                        $schema_table->addColumn($column_name, $column->type);
                    }
                }
            }
            if($read->has('Schema.primary_key')){
                $schema_table->setPrimaryKey($read->get('Schema.primary_key'));
            }
            if($read->has('Schema.unique')){
                foreach($read->get('Schema.unique') as $index){
                    if(is_array($index)){
                        $schema_table->addUniqueIndex($index);
                    } else {
                        $schema_table->addUniqueIndex([$index]);
                    }
                }
            }
            if($read->has('Schema.index')){
                foreach($read->get('Schema.index') as $index){
                    if(is_array($index)){
                        $schema_table->addIndex($index , 'idx_' . implode('_', $index));
                    } else {
                        $schema_table->addIndex([$index] , 'idx_' . $index);
                    }
                }
            }
            return $schema->toSql($options->platform);
        }
        return [];
    }

}