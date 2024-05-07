<?php
namespace R3m\Io\Doctrine\Service;

use R3m\Io\App;

use Exception;
use R3m\Io\Module\Controller;

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
                $data = [];
                $data[] = '<?php';
                $data[] = '';
                $data[] = 'namespace Entity;';

                $columns = $read->get('Schema.columns');
                $data_columns = [];
                $data_functions = [];
                $encrypted = [];
                if($columns && is_array($columns)){
                    foreach($columns as $nr => $column){
                        $is_set = true;
                        $is_get = true;
                        $is_both = true;
                        $options_default = null;
                        if(
                            property_exists($column, 'options') &&
                            property_exists($column->options, 'id') &&
                            $column->options->id === true
                        ){
                            $data_columns[] = '#[ORM\Id]';
                            $is_set = false;
                            $is_both = false;
                        }
                        if(
                            property_exists($column, 'type')
                        ){
                            $column_value = 'type: ' . $column->type;
                            $column_value .= ', name: "`' . $column->name . '`"';
                            $is_null = false;
                            d($column->options ?? '');
                            if(
                                property_exists($column, 'options') &&
                                property_exists($column->options, 'encryption') &&
                                $column->options->encryption === true
                            ){
                                $encrypted[] = $column->name;
                            }
                            if(
                                property_exists($column,'options') &&
                                property_exists($column->options, 'unique') &&
                                $column->options->unique === true
                            ){
                                $column_value .= ', unique: true';
                            }
                            if(
                                property_exists($column, 'options') &&
                                property_exists($column->options, 'nullable') &&
                                $column->options->nullable === true
                            ){
                                $column_value .= ', nullable: true';
                                $is_null = true;
                            }
                            if(
                                property_exists($column, 'options') &&
                                property_exists($column->options, 'default')
                            ){
                                if($column->options->default !== null){
                                    if(is_numeric($column->options->default)){
                                        $options_default = $column->options->default + 0;
                                        $column_value .= ', options: ["default": ' . $options_default . ']';
                                    } else {
                                        $column_value .= ', options: ["default": "' . $column->options->default . '"]';
                                    }
                                }
                            }
                            $data_columns[] = '#[ORM\column(' . $column_value . ')]';
                        }
                        if(
                            property_exists($column, 'options') &&
                            property_exists($column->options, 'autoincrement')
                        ){
                            if(is_string($column->options->autoincrement)){
                                $data_columns[] = '#[ORM\GeneratedValue(strategy: "' . $column->options->autoincrement . '")]';
                            }
                            elseif(
                                is_bool($column->options->autoincrement) &&
                                $column->options->autoincrement === true
                            ) {
                                $data_columns[] = '#[ORM\GeneratedValue(strategy: "AUTO")]';
                            }
                        }
                        $type = null;
                        switch($column->type){
                            case 'smallint':
                            case 'integer':
                                $type = 'int';
                                break;
                            case 'double':
                            case 'float':
                                $type = 'float';
                                break;
                            case 'array':
                            case 'simple_array':
                                $type = 'array';
                                break;
                            case 'object':
                                $type = 'object';
                                break;
                            case 'time':
                            case 'date':
                            case 'datetime':
                            case 'datetimetz':
                                $type = 'DateTime';
                                break;
                            case 'time_immutable':
                            case 'date_immutable':
                            case 'datetimetz_immutable':
                                $type = 'DateTimeImmutable';
                                break;
                            case 'dateinterval':
                                $type = 'DateInterval';
                                break;
                            case 'decimal':
                            case 'bigint':
                            case 'text':
                            case 'ascii_string':
                            case 'varchar':
                            case 'guid':
                                $type = 'string';
                                break;
                            case 'blob':
                            case 'binary':
                                $type = 'resource';
                                break;
                            case 'json':
                                $type = 'mixed';
                                break;
                            case 'boolean':
                                $type = 'bool';
                                break;
                            default :
                                $type = 'string';
                                break;

                        }
                        if($is_both){
                            $both = [];
                            $both[] = 'public function ' . str_replace('.', '',lcfirst(Controller::name($column->name))) . '(' . $type . ' $' . $column->name . '=null): ?' . $type;
                            $both[] = '{';
                            $both[] = '    if($' . $column->name . ' !== null){';
                            $both[] = '        $this->set' . str_replace('.', '', Controller::name($column->name)) . '($' . $column->name . ');';
                            $both[] = '    }';
                            $both[] = '    return $this->get' . str_replace('.', '', Controller::name($column->name)) . '();';
                            $both[] = '}';
                            $data_functions[] = $both;
                        }
                        if($is_null){
                            $data_columns[] = 'protected ?' . $type . ' $' . $column->name . ' = null;';
                            if($is_set){
                                $set = [];
                                $set[] = 'public function set' . str_replace('.', '', Controller::name($column->name)) . '(' . $type . ' $' . $column->name . '=null): void';
                                $set[] = '{';
                                $set[] = '    $this->' . $column->name . ' = $' . $column->name . ';';
                                $set[] = '}';
                                $data_functions[] = $set;
                            }
                            if($is_get){
                                if(array_key_exists(0, $encrypted)){
                                    $get = [];
                                    $get[] = 'public function get' . str_replace('.', '', Controller::name($column->name)) . '(): ?' . $type;
                                    $get[] = '{';
                                    $get[] = '    try {';
                                    $get[] = '        $object = $this->object();';
                                    $get[] = '        return $this->' . $column->name . ';';
                                    $get[] = '    } catch (Exception | BadFormatException | EnvironmentIsBrokenException | WrongKeyOrModifiedCiphertextException $exception) {';
                                    $get[] = '        return null;';
                                    $get[] = '    }';
                                    $get[] = '}';
                                } else {
                                    $get = [];
                                    $get[] = 'public function get' . str_replace('.', '', Controller::name($column->name)) . '(): ?' . $type;
                                    $get[] = '{';
                                    $get[] = '    return $this->' . $column->name . ';';
                                    $get[] = '}';
                                }

                                $data_functions[] = $get;
                            }
                        } else {
                            if($options_default !== null){
                                $data_columns[] = 'protected ' . $type . ' $' . $column->name . ' = ' . $options_default . ';';
                            }
                            elseif(
                                property_exists($column, 'options') &&
                                property_exists($column->options, 'default') &&
                                $column->options->default !== null
                            ){
                                $data_columns[] = 'protected ' . $type . ' $' . $column->name . ' = "' . $column->options->default . '";';
                            }
                            else {
                                $data_columns[] = 'protected ' . $type . ' $' . $column->name . ';';
                            }

                            if($is_set){
                                $set = [];
                                $set[] = 'public function set' . str_replace('.', '', Controller::name($column->name)) . '(' . $type . ' $' . $column->name . '): void';
                                $set[] = '{';
                                $set[] = '    $this->' . $column->name . ' = $' . $column->name . ';';
                                $set[] = '}';
                                $data_functions[] = $set;
                            }
                            if($is_get){
                                $get = [];
                                $get[] = 'public function get' . str_replace('.', '', Controller::name($column->name)) . '(): ' . $type;
                                $get[] = '{';
                                $get[] = '    return $this->' . $column->name . ';';
                                $get[] = '}';
                                $data_functions[] = $get;
                            }
                        }
                    }
                }
                $use = [];
                $use[] = 'DateTime';
                $use[] = '';

                ddd($encrypted);

                if(array_key_exists(0, $encrypted)){
                    $use[] = 'Defuse\Crypto\Crypto';
                    $use[] = 'Defuse\Crypto\Exception\BadFormatException';
                    $use[] = 'Defuse\Crypto\Exception\EnvironmentIsBrokenException';
                    $use[] = 'Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException';
                    $use[] = 'Defuse\Crypto\Key';
                    $use[] = '';
                    $use[] = 'R3m\Io\App';
                    $record_object = [];
                    $record_object[] = 'public function object($object=null): App';
                    $record_object[] = '{';
                    $record_object[] = '    if($object !== null){';
                    $record_object[] = '        $this->setObject($object);';
                    $record_object[] = '    }';
                    $record_object[] = '    return $this->getObject();';
                    $record_object[] = '}';
                    $record_object_set = [];
                    $record_object_set[] = 'public function setObject(App $object): void';
                    $record_object_set[] = '{';
                    $record_object_set[] = '    $this->object = $object;';
                    $record_object_set[] = '}';
                    $record_object_get = [];
                    $record_object_get[] = 'public function getObject(): App';
                    $record_object_get[] = '{';
                    $record_object_get[] = '    return $this->object;';
                    $record_object_get[] = '}';
                    $data_functions[] = $record_object;
                    $data_functions[] = $record_object_set;
                    $data_functions[] = $record_object_get;
                }
                $use[] = '';
                $use[] = 'Doctrine\ORM\Mapping as ORM';
                $use[] = 'Doctrine\ORM\Mapping\PrePersist';
                $use[] = 'Doctrine\ORM\Mapping\PreUpdate';
                $use[] = 'Doctrine\ORM\Event\PrePersistEventArgs';
                $use[] = 'Doctrine\ORM\Event\PreUpdateEventArgs';
                $use[] = '';
                $use[] = 'R3m\Io\Module\Core';
                $use[] = 'R3m\Io\Module\File';
                $use[] = '';
                $use[] = 'Exception';
                $use[] = '';
                $use[] = 'R3m\Io\Exception\FileWriteException';
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
                foreach($data_columns as $nr => $row){
                    $data[] = '    ' . $row;
                }
                $data[] = '';
                foreach ($data_functions as $nr => $set){
                    foreach($set as $nr => $row){
                        $data[] = '    ' . $row;
                    }
                    $data[] = '';
                }
                $data[] = '}';
                ddd($data);
            }
        }

        ddd($options);
    }

}