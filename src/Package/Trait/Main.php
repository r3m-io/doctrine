<?php
namespace Package\R3m\Io\Doctrine\Trait;

use Doctrine\DBAL\Schema\Schema;

use R3m\Io\Config;

use R3m\Io\Module\Core;
use R3m\Io\Module\File;
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
    public function schema_import($flags=null, $options=null): void
    {
        $object = $this->object();
        $is_force = false;
        if(!property_exists($options, 'url')){
            throw new Exception('Url not set...');
        }
        if(property_exists($options, 'force')){
            $is_force = $options->force;
        }
        $node = new Node($object);
        if(
            property_exists($options, 'environment') &&
            is_array($options->environment)
        ){
            foreach($options->environment as $nr => $environment){
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
                        $options->environment[$nr] = $record['node']->uuid;
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
                            $options->environment[$nr] = $record['node']->uuid;
                        } else {
                            throw new Exception('Environment not found...');
                        }
                    }
                }
            }
        }
        if(!property_exists($options, 'environment')){
            throw new Exception('System.Doctrine.Environment not set... use option -environment[]');
        }

        //default value for each import
        // system.doctrine.environment is user input
        $options->node = (object) [
            'default' => (object) [
                'environment' => $options->environment
            ]
        ];
        $class = 'System.Doctrine.Schema';
        $role = $node->role_system();
        $import = $node->import($class, $role, $options);
        ddd($import);
    }


}