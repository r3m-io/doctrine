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
        d($options->url);
        $read = $object->data_read($options->url);
        if($read){
            $has_schema = $read->has('Schema');
            if($has_schema){
                $table = $read->get('Schema.table');
                $entity = $read->get('Schema.entity');
                $target = $object->config('project.dir.source') .
                    'Entity' .
                    $object->config('ds') .
                    $entity .
                    $object->config('extension.php')
                ;

                $use = [];
                $use[] = 'DateTime';
                $use[] = '';
                $use[] = 'Defuse\Crypto\Crypto';
                $use[] = 'Defuse\Crypto\Exception\BadFormatException';
                $use[] = 'Defuse\Crypto\Exception\EnvironmentIsBrokenException';
                $use[] = 'Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException';
                $use[] = 'Defuse\Crypto\Key';
                $use[] = '';
                $use[] = 'Doctrine\ORM\Mapping as ORM';
                $use[] = 'Doctrine\ORM\Mapping\PrePersist';
                $use[] = 'Doctrine\ORM\Mapping\PreUpdate';
                $use[] = 'Doctrine\ORM\Event\PrePersistEventArgs';
                $use[] = 'Doctrine\ORM\Event\PreUpdateEventArgs';
                $use[] = '';
                $use[] = 'R3m\Io\App';
                $use[] = '';
                $use[] = 'R3m\Io\Module\Core';
                $use[] = 'R3m\Io\Module\File';
                $use[] = '';
                $use[] = 'Exception';
                $use[] = '';
                $use[] = 'R3m\Io\Exception\FileWriteException';

                $data = [];
                $data[] = '<?php';
                $data[] = '';
                $data[] = 'namespace Entity;';
                foreach($use as $usage){
                    if($usage === ''){
                        $data[] = '';
                    } else {
                        $data[] = 'use ' . $usage . ';';
                    }
                }
                $data[] = '';
                $data[] = '#[ORM\Entity]';
                $data[] = '#[ORM\Table(name: "' . $table . '")]';
                $data[] = '#[ORM\HasLifecycleCallbacks]';
                $data[] = 'class ' . $entity . ' {';
                $data[] = '';

                $columns = $read->get('Schema.columns');
                if($columns && is_array($columns)){
                    foreach($columns as $nr => $column){
                        ddd($column);
                    }
                }

                $data[] = '';
                $data[] = '}';
                ddd($data);
            }
        }

        ddd($options);
    }

}