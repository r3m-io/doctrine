<?php
namespace Package\R3m\Io\Doctrine\Trait;

use R3m\Io\Config;

use R3m\Io\Module\Core;
use R3m\Io\Module\Database;
use R3m\Io\Module\Event;
use R3m\Io\Module\File;

use R3m\Io\Node\Model\Node;

use R3m\Io\Doctrine\Service\Table;


use Exception;

use R3m\Io\Exception\ObjectException;

trait Main {

    /**
     * @throws ObjectException
     * @throws Exception
     */
    public function system_config($options=[]): void
    {
        $object = $this->object();
        $posix_id = $object->config(Config::POSIX_ID);
        if(
            !in_array(
                $posix_id,
                [
                    0,
                    33
                ],
                true
            )
        ){
            throw new Exception('Access denied...');
        }
        $node = new Node($object);
        $config = $node->record('System.Config', $node->role_system());
        if(
            $config &&
            is_array($config) &&
            array_key_exists('node', $config) &&
            property_exists($config['node'], 'uuid') // &&
//            !property_exists($config['node'], 'doctrine')
        ){
            $patch = (object) [
                'uuid' => $config['node']->uuid,
                'doctrine' => '*'
            ];
            $config = $node->patch('System.Config', $node->role_system(), $patch);
            if(
                is_array($config) &&
                array_key_exists('node', $config)
            ){
                //nothing
            } else {
                throw new Exception('Could not patch node System.Config');
            }
        }
    }

    /**
     * @throws Exception
     */
    public function bin_doctrine($options=[]){
        $object = $this->object();
        $posix_id = $object->config(Config::POSIX_ID);
        if(
            !in_array(
                $posix_id,
                [
                    0,
                ],
                true
            )
        ){
            throw new Exception('Access denied...');
        }
        $url_bin = $object->config('project.dir.vendor') . 'r3m_io/doctrine/src/Bin/Doctrine.php';
        $url_target = $object->config('project.dir.binary') . 'Doctrine.php';
        File::copy($url_bin, $url_target);
        $url_bin_source = $object->config('project.dir.vendor') . 'r3m_io/doctrine/src/Bin/doctrine';
        $url_bin_target = '/usr/bin/doctrine';
        File::copy($url_bin_source, $url_bin_target);
        $command = 'chmod +x ' . $url_bin_target;
        exec($command);
        File::permission($object, [
            'url_target' => $url_target,
        ]);
    }

    /**
     * @throws Exception
     */
    public function table_all($flags=null, $options=null): array
    {
        if(!property_exists($options, 'connection')){
            throw new Exception('Option: connection not set...');
        }
        $object = $this->object();
        if(
            is_string($options->connection)
        ){
            $options->connection = [$options->connection];
        }
        if(
            is_array($options->connection)
        ) {
            $node = new Node($object);
            $record = false;
            $environment = $options->environment ?? $object->config('framework.environment');
            foreach ($options->connection as $nr => $connection) {
                if (!Core::is_uuid($connection)) {
                    $class = 'System.Doctrine.Environment';
                    $role = $node->role_system();
                    $record = $node->record(
                        $class,
                        $role,
                        [
                            'filter' => [
                                'name' => $connection,
                                'environment' => $environment
                            ]
                        ]
                    );
                    if (
                        $record &&
                        array_key_exists('node', $record) &&
                        property_exists($record['node'], 'uuid')
                    ) {
                        $options->connection[$nr] = $record['node']->uuid;
                    } else {
                        $record = $node->record(
                            $class,
                            $role,
                            [
                                'filter' => [
                                    'name' => $connection,
                                    'environment' => '*'
                                ]
                            ]
                        );
                        if (
                            $record &&
                            array_key_exists('node', $record) &&
                            property_exists($record['node'], 'uuid')
                        ) {
                            $options->connection[$nr] = $record['node']->uuid;
                        } else {
                            throw new Exception('Environment not found...');
                        }
                    }
                    if($record){
                        break;
                    }
                }
            }
        }
        if(
            $record &&
            array_key_exists('node', $record)
        ){
            $config = $record['node'];
            if(
                property_exists($config, 'name') &&
                property_exists($config, 'environment')
            ){
                Database::instance($object, $config->name, $config->environment);
                return Table::all($object, $config->name, $config->environment);
            }

        }
        return [];
    }

    /**
     * @throws Exception
     */
    public function table_truncate($flags=null, $options=null): array
    {
        if(!property_exists($options, 'connection')){
            throw new Exception('Option: connection not set...');
        }
        if(!property_exists($options, 'table')){
            throw new Exception('Option: table not set...');
        }
        $object = $this->object();
        if(
            is_string($options->connection)
        ){
            $options->connection = [$options->connection];
        }
        if(
            is_array($options->connection)
        ) {
            $node = new Node($object);
            $record = false;
            $environment = $options->environment ?? $object->config('framework.environment');
            foreach ($options->connection as $nr => $connection) {
                if (!Core::is_uuid($connection)) {
                    $class = 'System.Doctrine.Environment';
                    $role = $node->role_system();
                    $record = $node->record(
                        $class,
                        $role,
                        [
                            'filter' => [
                                'name' => $connection,
                                'environment' => $environment
                            ]
                        ]
                    );
                    if (
                        $record &&
                        array_key_exists('node', $record) &&
                        property_exists($record['node'], 'uuid')
                    ) {
                        $options->connection[$nr] = $record['node']->uuid;
                    } else {
                        $record = $node->record(
                            $class,
                            $role,
                            [
                                'filter' => [
                                    'name' => $connection,
                                    'environment' => '*'
                                ]
                            ]
                        );
                        if (
                            $record &&
                            array_key_exists('node', $record) &&
                            property_exists($record['node'], 'uuid')
                        ) {
                            $options->connection[$nr] = $record['node']->uuid;
                        } else {
                            throw new Exception('Environment not found...');
                        }
                    }
                    if($record){
                        break;
                    }
                }
            }
        }
        if(
            $record &&
            array_key_exists('node', $record)
        ){
            $config = $record['node'];
            if(
                property_exists($config, 'name') &&
                property_exists($config, 'environment')
            ){
                Database::instance($object, $config->name, $config->environment);
                return Table::truncate($object, $config->name, $config->environment, $options);
            }

        }
        return [];
    }

    /**
     * @throws Exception
     */
    public function schema_import($flags=null, $options=null): void
    {
        $object = $this->object();
        $is_force = false;
        if(!property_exists($options, 'url')){
            throw new Exception('Option: url not set...');
        }
        if(!property_exists($options, 'connection')){
            throw new Exception('Option: connection not set...');
        }
        if(property_exists($options, 'force')){
            $is_force = $options->force;
        }
        $node = new Node($object);
        if(
            is_string($options->connection)
        ){
            $options->connection = [$options->connection];
        }
        if(
            is_array($options->connection)
        ){
            foreach($options->connection as $nr => $environment){
                if(!Core::is_uuid($environment)){
                    $class = 'System.Doctrine.Environment';
                    $role = $node->role_system();
                    $record = $node->record(
                        $class,
                        $role,
                        [
                            'filter' => [
                                'name' => $environment,
                                'environment' => $object->config('framework.environment')
                            ]
                        ]
                    );
                    if(
                        $record &&
                        array_key_exists('node', $record) &&
                        property_exists($record['node'], 'uuid')
                    ){
                        $options->connection[$nr] = $record['node']->uuid;
                    } else {
                        $record = $node->record(
                            $class,
                            $role,
                            [
                                'filter' => [
                                    'name' => $environment,
                                    'environment' => '*'
                                ]
                            ]
                        );
                        if(
                            $record &&
                            array_key_exists('node', $record) &&
                            property_exists($record['node'], 'uuid')
                        ){
                            $options->connection[$nr] = $record['node']->uuid;
                        } else {
                            throw new Exception('Environment not found...');
                        }
                    }
                }
            }
        }
        //default value for each import
        // system.doctrine.environment is user input
        $options->node = (object) [
            'default' => (object) [
                'environment' => $options->connection
            ]
        ];
        if(
            property_exists($options, 'event') &&
            $options->event === true
        ){
            $actions = [
                'create',
                'patch',
                'put'
            ];
            $events =[];

            foreach($actions as $action){
                $events[] = (object) [
                    '#class' => 'System.Event',
                    'action' => 'r3m.io.node.' . $action,
                    'options' => (object) [
                        'priority' => 10,
                        'command' => [],
                        'controller' => [
                            'Event:R3m:Io:Doctrine:Schema:create'
                        ]
                    ]
                ];
            }
            $action = 'delete';
            $events[] = (object) [
                '#class' => 'System.Event',
                'action' => 'r3m.io.node.' . $action,
                'options' => (object) [
                    'priority' => 10,
                    'command' => [],
                    'controller' => [
                        'Event:R3m:Io:Doctrine:Schema:' . $action
                    ]
                ]
            ];
            Event::on($object, $events);
//            Doctrine\ORM\Tools\SchemaTool::getSchemaFromMetadata(array $classes): Schema
            //load events for:
            // move events to the import url json file
            // - r3m.io.node.create (schema) -> create entity, create table(s)
            // - r3m.io.node.patch (schema) -> create entity, patch table(s)
            // - r3m.io.node.put (schema) -> create entity, patch table(s)
            // - r3m.io.node.delete (schema) -> delete entity, drop table(s)
        }
        $class = 'System.Doctrine.Schema';
        $role = $node->role_system();
        $import = $node->import($class, $role, $options);
        echo Core::object($import, Core::OBJECT_JSON) . PHP_EOL;
    }
}