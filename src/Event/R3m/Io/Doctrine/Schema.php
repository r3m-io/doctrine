<?php

namespace Event\R3m\Io\Doctrine;

use R3m\Io\App;
use R3m\Io\Config;

use R3m\Io\Module\File;
use R3m\Io\Module\Parse;

use R3m\Io\Doctrine\Service\Schema as SchemaService;

use Exception;

class Schema {

    /**
     * @throws Exception
     */
    public static function create(App $object, $event, $options=[]): void
    {
        SchemaService::entity($object,
            $options['class'],
            $options['role'],
            $options['node']
        );
        SchemaService::repository($object,
            $options['class'],
            $options['role'],
            $options['node']
        );


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