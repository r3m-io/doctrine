<?php

namespace Package\R3m\Io\Config\Output\Filter\System;

use R3m\Io\App;

use R3m\Io\Module\Controller;

class Server extends Controller {
    const DIR = __DIR__ . '/';

    public static function environment(App $object, $response=null): object
    {
        $result = [];
        ddd($response);
        if(
            !empty($response) &&
            is_array($response)
        ){
            foreach($response as $nr => $record){
                if(
                    is_array($record) &&
                    array_key_exists('name', $record) &&
                    array_key_exists('options', $record)
                ){
                    $result[$record['name']] = $record['options'];
                }
                elseif(
                    is_object($record) &&
                    property_exists($record, 'name') &&
                    property_exists($record, 'options')
                ){
                    $result[$record->name] = $record->options;
                }
            }
        }
        return (object) $result;
    }
}