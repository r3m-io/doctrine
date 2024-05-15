<?php
namespace R3m\Io\Doctrine\Service;

use R3m\Io\App;
use R3m\Io\Module\Core;
use R3m\Io\Module\Database;

use Exception;

class Index extends Main

{

    /**
     * @throws Exception
     */
    public static function all(App $object, $name, $environment=null, $options=null): array
    {
        if (!property_exists($options, 'table')) {
            throw new Exception('table not set in options');
        }
        if($environment === null){
            $environment = $object->config('environment');
        } else {
            $environment = str_replace('.', '-', $environment);
        }
        $name = str_replace('.', '-', $name);
        try {
            $schema_manager = Database::schema_manager($object, $name, $environment);
        }
        catch(Exception $exception){
            try {
                Database::instance($object, $name, $environment);
                $schema_manager = Database::schema_manager($object, $name, $environment);
            }
            catch(Exception $exception){
                return [];
            }
        }
        $sanitized_table = preg_replace('/[^a-zA-Z0-9_]/', '', $options->table);
        $indexes = [];
        if($schema_manager){
            $indexes = $schema_manager->listTableIndexes($sanitized_table);
        }
        return $indexes;
    }
}