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
            $list = $schema_manager->listTableIndexes($sanitized_table);
            foreach($list as $index){
                d($index);
                d(get_class_methods($index));
                $record = (object) [
                    'column' => $index->getColumns(),
                    'quoted_column' => $index->getQuotedColumns(),
                    'unquoted_column' => $index->getUnQuotedColumns(),
                    'is_simple_index' => $index->isSimpleIndex(),
                    'is_unique' => $index->isUnique(),
                    'is_primary' => $index->isPrimary(),
                    'flags' => $index->getFlags(),
                    'options' => $index->getOptions(),
                    'namespace' => $index->getNamespaceName(),
                    'is_default_namespace' => $index->getIsInDefaultNamespace(),
                    'name' => $index->getName(),
                    'is_quoted' => $index->isQuoted(),
                    'name_quoted' => $index->getQuotedName(),
                ];
                $indexes[] = $record;
            }
            ddd($indexes);
        }
        return $indexes;
    }
}