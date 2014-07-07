<?php
namespace Application\Model;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use MongoId;
use MongoDBRef;
use MongoException;

class Mapper implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $services)
    {
        $this->mongodb = $services->get('mongodb');
        return $this;
    }

    public function getDBRef(array $ref)
    {
        if($ref['$ref'] === 'jobs') {
            return $this->findJob($ref['$id']);
        }
        if($ref['$ref'] === 'builds') {
            return $this->findBuild($ref['$id']);
        }
        return null;
    }
    
    public function findObject($table,$type,$id)
    {
        $type = 'Application\\Model\\' . $type;
        if(!($id instanceof MongoId) && $id !== null) {
            try {
                $id = new MongoId($id);
            } catch(MongoException $e) {
                $id = null;
            }
        }
        $data = $this->mongodb->$table->findOne(['_id' => $id]);
        if($data === null) {
            return null;
        }
        return new $type($data,$this->mongodb->$table);
    }

    public function findObjectByAttr($table,$type,$attr,$value)
    {
        $type = 'Application\\Model\\' . $type;
        $data = $this->mongodb->$table->findOne([$attr => $value]);
        if($data === null) {
            return null;
        }
        return new $type($data,$this->mongodb->$table);
    }

    public function fetchObjects($table,$type,array $selector = [],array $order = null)
    {
        $type = 'Application\\Model\\' . $type;
        $cursor = $this->mongodb->$table->find($selector);
        if($order) {
            $cursor->sort($order);
        }
        $result = [];
        foreach($cursor as $data) {
            $obj = new $type($data,$this->mongodb->$table);
            $result[] = $obj;
        }
        return $result;
    }

    public function countObjects($table,$query = array())
    {
        $cursor = $this->mongodb->$table->find($query);
        return $cursor->count();
    }

    public function newObject($table,$type,$data)
    {
        $type = 'Application\\Model\\' . $type;
        return new $type($data,$this->mongodb->$table);
    }
}
