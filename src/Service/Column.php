<?php
namespace R3m\Io\Doctrine\Service;

use R3m\Io\App;
use R3m\Io\Module\Core;
use R3m\Io\Module\Database;
use R3m\Io\Module\File;

use Exception;

class Column extends Main

{
    /**
     * @throws Exception
     */
    public static function all(App $object, $name, $environment = null, $options = null): array
    {
        $options = Core::object($options);
        if ($environment === null) {
            $environment = $object->config('environment');
        } else {
            $environment = str_replace('.', '-', $environment);
        }
        $name = str_replace('.', '-', $name);
        if (!property_exists($options, 'table')) {
            throw new Exception('table not set in options');
        }
        try {
            $schema_manager = Database::schema_manager($object, $name, $environment);
        }
        catch (Exception $exception) {
            try {
                Database::instance($object, $name, $environment);
                $schema_manager = Database::schema_manager($object, $name, $environment);
            } catch (Exception $exception) {
                return [];
            }
        }
        if (!$schema_manager) {
            return [];
        }
        $tables = Table::all($object, $name, $environment);
        $sanitized_table = preg_replace('/[^a-zA-Z0-9_]/', '', $options->table);
        if (in_array($sanitized_table, $tables, true)) {
            $columns = $schema_manager->listTableColumns($sanitized_table);
            if($columns){
                $list = [];
                foreach($columns as $column){
                    $record = $column->toArray();
                    $record['type'] = File::basename(get_class($column->getType()));
                    switch(strtolower($record['type'])){
                        case 'integertype':
                            $record['type'] = 'integer';
                            break;
                        case 'smallinttype':
                            $record['type'] = 'smallint';
                            break;
                        case 'tinyinttype':
                            $record['type'] = 'tinyint';
                            break;
                        case 'biginttype':
                            $record['type'] = 'bigint';
                            break;
                        case 'stringtype':
                            $record['type'] = 'string';
                            break;
                        case 'texttype':
                            $record['type'] = 'text';
                            break;
                        case 'booleantype':
                            $record['type'] = 'boolean';
                            break;
                        case 'floattype':
                            $record['type'] = 'float';
                            break;
                        case 'decimaltype':
                            $record['type'] = 'decimal';
                            break;
                        case 'binarytype':
                            $record['type'] = 'binary';
                            break;
                        case 'datetimetype':
                            $record['type'] = 'datetime';
                            break;
                        case 'datetimetztype':
                            $record['type'] = 'datetimetz';
                            break;
                        case 'timetype':
                            $record['type'] = 'time';
                            break;
                        default:
                            throw new Exception('unknown type: ' . $record['type']);
                    }
                    $list[]= $record;
                }
                return $list;
            }
        }
        return [];
    }
}
