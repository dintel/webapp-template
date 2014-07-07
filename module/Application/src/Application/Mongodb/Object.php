<?php

namespace Application\Mongodb;

use MongoId;
use MongoDBRef;
use MongoDate;
use MongoCollection;
use JsonSerializable;
use ArrayObject;

class Object implements JsonSerializable
{
    const TYPE_ID=0;
    const TYPE_BOOL=1;
    const TYPE_INT=2;
    const TYPE_DOUBLE=3;
    const TYPE_STRING=4;
    const TYPE_ARRAY=5;
    const TYPE_DATE=6;
    const TYPE_REFERENCE=7;
    const TYPE_ARRAY_OBJECT=8;

    protected $_schema;
    protected $_data;
    protected $_collection;
    
    public function __construct(array $schema,array $data,MongoCollection $collection)
    {
        $this->_schema = $schema;
        $this->_data = $data;
        $this->_collection = $collection;

        foreach($this->_schema as $name => $desc) {
            if(!isset($this->_data[$name])) {
                $this->_data[$name] = null;
            }
            $this->convertProperty($name);
        }
    }

    private function convertProperty($name)
    {
        if($this->_schema[$name]['null'] && $this->_data[$name] === null)
            return;

        switch($this->_schema[$name]['type']) {
        case Object::TYPE_ID:
            if(!($this->_data[$name] instanceof MongoId) && $this->_data[$name] !== null)
                $this->_data[$name] = new MongoId($this->_data[$name]);
            break;
        case Object::TYPE_BOOL:
            if(!is_bool($this->_data[$name]))
                $this->_data[$name] = (bool) $this->_data[$name];
            break;
        case Object::TYPE_INT:
            if(!is_int($this->_data[$name]))
                $this->_data[$name] = (int) $this->_data[$name];
            break;
        case Object::TYPE_DOUBLE:
            if(!is_double($this->_data[$name]))
                $this->_data[$name] = (double) $this->_data[$name];
            break;
        case Object::TYPE_STRING:
            if(!is_string($this->_data[$name]))
                $this->_data[$name] = (string) $this->_data[$name];
            break;
        case Object::TYPE_ARRAY:
            if(!is_array($this->_data[$name]))
                $this->_data[$name] = array();
            break;
        case Object::TYPE_DATE:
            if(!($this->_data[$name] instanceof MongoDate))
                $this->_data[$name] = new MongoDate($this->_data[$name]);
            break;
        case Object::TYPE_REFERENCE:
            /* if(!MongoDBRef::isRef($this->_data[$name])) */
            /*     $this->_data[$name] = null; */
            break;
        case Object::TYPE_ARRAY_OBJECT:
            if(!($this->_data[$name] instanceof ArrayObject)) {
                throw new \Exception("Property '{$name}' must be an object of type ArrayObject");
            }
        default:
            throw new \Exception("Property '{$name}' type is unknown ({$this->_schema[$name]['type']})");
        }
    }
    
    public function __isset($name)
    {
        return isset($this->_data[$name]);
    }

    public function __set($name,$value)
    {
        if(!isset($this->_schema[$name]))
            throw new \Exception("Property '{$name}' could not be set, does not exist in schema.");
        if(isset($this->_schema[$name]['hidden']) && $this->_schema[$name]['hidden'])
            throw new \Exception("Property '{$name}' could not be set, it is hidden.");
        
        $this->_data[$name] = $value;
        $this->convertProperty($name);
    }

    public function __get($name)
    {
        if(!isset($this->_schema[$name]))
            throw new \Exception("Property '{$name}' could not be get, does not exist in schema.");
        if(isset($this->_schema[$name]['hidden']) && $this->_schema[$name]['hidden'])
            throw new \Exception("Property '{$name}' could not be get, it is hidden.");
        
        return $this->_data[$name];
    }

    public function save()
    {
        if($this->_data['_id'] === null)
            unset($this->_data['_id']);
        if(isset($this->_data['modified']))
            $this->_data['modified'] = new MongoDate();
        file_put_contents("/tmp/dimatest",var_export($this->_data,true));
        return $this->_collection->save($this->_data);
    }

    public function delete()
    {
        if(!$this->isNew()) {
            return $this->_collection->remove(['_id' => $this->_id]);
        }
    }

    public function isNew()
    {
        return $this->_id === null;
    }

    public function getDBRef()
    {
        return MongoDBRef::create($this->_collection->getName(),$this->_id);
    }

    protected function fetchDBRef($collectionName,$typeName,$dbref)
    {
    	$typeName = 'Application\\Model\\' . $typeName;
        $collection = $this->_collection->db->$collectionName;
        if($dbref == null) {
        	return null;
        }
        $data = $this->_collection->getDBRef($dbref);
        if($data == null) {
        	return null;
        }
        return new $typeName($data,$collection);
    }
    
    public function refresh()
    {
        if($this->_data['_id'] !== null) {
            $this->_data = $this->_collection->findOne(['_id' => $this->_data['_id']]);
            foreach($this->_schema as $name => $desc) {
                if(!isset($this->_data[$name])) {
                    $this->_data[$name] = null;
                }
                $this->convertProperty($name);
            }
        }
    }

    public function jsonSerialize()
    {
        $jsonData = $this->_data;
        if(isset($jsonData["_id"])) {
            $jsonData["id"] = (string)$jsonData["_id"];
            unset($jsonData["_id"]);
        }
        foreach($this->_schema as $name => $dsc) {
            if(@$dsc['hidden']) {
                unset($jsonData[$name]);
            }
        }
        return $jsonData;
    }

    public function mergeData(array $data)
    {
        $this->_data = $data + $this->_data;
    }
}
