<?php
namespace R3m\Io\Doctrine\Service;

use R3m\Io\App;

use Exception;

class Schema extends Main

{

    /**
     * @throws Exception
     */
    public static function entity(App $object, $options): void
    {
        /*
        if(!property_exists($options, 'platform')){
            throw new Exception('Option, Platform not set...');
        }
        */
        if(!property_exists($options, 'url')){
            throw new Exception('Option, Url not set...');
        }
        ddd($options);
    }

}