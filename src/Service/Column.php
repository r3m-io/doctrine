<?php
namespace R3m\Io\Doctrine\Service;

use R3m\Io\App;
use R3m\Io\Module\Core;
use R3m\Io\Module\Database;

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
        $connection = Database::connection($object, $name, $environment);
        if (!$connection) {
            Database::instance($object, $name, $environment);
            $connection = Database::connection($object, $name, $environment);
        }
        $tables = Table::all($object, $name, $environment);
        $sanitized_table = preg_replace('/[^a-zA-Z0-9_]/', '', $options->table);
        if (in_array($options->table, $tables, true)) {
            $sql = "PRAGMA table_info($sanitized_table)";
            $stmt = $connection->executeQuery($sql);
            return $stmt->fetchAllAssociative();
        }
        return [];
    }
}
