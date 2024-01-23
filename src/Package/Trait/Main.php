<?php
namespace Package\R3m\Io\Doctrine\Trait;

use R3m\Io\Config;

use R3m\Io\Node\Model\Node;

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
                ]
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
}