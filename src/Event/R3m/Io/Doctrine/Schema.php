<?php

namespace Event\R3m\Io\Doctrine;

use R3m\Io\App;
use R3m\Io\Config;

use R3m\Io\Module\File;
use R3m\Io\Module\Parse;

use R3m\Io\Doctrine\Service\Schema as SchemaService;
use R3m\Io\Doctrine\Service\Table;

use Exception;

class Schema {

    /**
     * @throws Exception
     */
    public static function create(App $object, $event, $options=[]): void
    {
        //if exist rename table
//        d($options);
        $node = false;
        if(array_key_exists('node', $options)){
            $node = $options['node'];

            if(property_exists($node, 'environment')){
                if(
                    is_array($node->environment) ||
                    is_object($node->environment)
                ){
                    foreach($node->environment as $name => $config){
                        $config->table = Table::all($object, $config->name, $config->environment);
                        d($name);
                        d($config);
                    }
                }
            }

            ddd($node->environment);
        }
        /*
            foreach($options['node']->environment as $name => $environment){
                $is_rename = false;
                $table = null;
                if(
                    Table::has(
                        $object,
                        $options['class'],
                        $options['role'],
                        $options['node'],
                        [
                            'environment' => $environment
                        ]
                    )
                ) {
                    $table = Table::rename(
                        $object,
                        $options['class'],
                        $options['role'],
                        $options['node']
                    );
                    ddd($table);
                    $is_rename = true;
                }
                SchemaService::entity($object,
                    $options['class'],
                    $options['role'],
                    $options['node']
                );
                //only create repository class if not exist, resetting means deleting the repository class and rerun this event
                SchemaService::repository($object,
                    $options['class'],
                    $options['role'],
                    $options['node']
                );
                if(
                    $is_rename &&
                    $table
                ){
                    Table::import(
                        $object,
                        $options['class'],
                        $options['role'],
                        $options['node'],
                        $table
                    );
                }
            }
        }
        */



        //if exist rename table
        //import data from rename table in to new table

        //need entity url
        //need previous entity url
        //create sql table when not exist
        //when table exist we should be carefully, perhaps a "rename" and "create" The safest way and then import the data from the original (rename)
        //a seperate command will then delete the renamed (original) table. (which can be done by the user)
        // this means that you must reserve 2x the amount of the table size in data terms when changing the schema, but is needed (backup if things went wrong).
        // the user can then delete the (renamed) original table when he is sure that the new table is working correctly.
        /**
         * options = [
         *  'class',
         *  'node',
         *  'options',
         *  'role' => check permission to create schema
         * ]
         */
        return;
        if($object->config(Config::POSIX_ID) !== 0){
            return;
        }
        $action = $event->get('action');
        if(array_key_exists('destination', $options)){
            $destination = $options['destination'];
            $destination = str_replace(
                [
                    '"{',
                    '}"'
                ],
                [
                    '{',
                    '}'
                ],
                $destination
            );
            $parse = new Parse($object);
            $parse->limit([
                'function' => [
                    'date'
                ]
            ]);
            $parse->limit([
                'function' => [
                    'date'
                ]
            ]);
            $destination = $parse->compile($destination, [], $object);
            $options['destination'] = $destination;
            if(File::Exist($destination)){
                \Event\R3m\Io\Framework\Email::queue(
                    $object,
                    $action,
                    $options
                );
            }
        }
    }
}