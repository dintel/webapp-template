<?php
namespace Application\Model;

use MongoObject\Object;
use MongoCollection;
use MongoDate;
use MongoDBRef;

class User extends Object
{
    const TYPE_ADMIN='admin';
    const TYPE_USER='user';

    public function __construct(array $data, MongoCollection $collection, $namespace)
    {
        $schema = [
            '_id' => ['type' => Object::TYPE_ID, 'null' => false],
            'login' => ['type' => Object::TYPE_STRING, 'null' => false],
            'type' => ['type' => Object::TYPE_STRING, 'null' => false],
            'name' => ['type' => Object::TYPE_STRING, 'null' => false],
            'email' => ['type' => Object::TYPE_STRING, 'null' => false],
            'password' => ['type' => Object::TYPE_STRING, 'null' => false, 'hidden' => true],
            'active' => ['type' => Object::TYPE_BOOL, 'null' => false],
        ];
        $defaults = [
            'active' => true,
        ];
        parent::__construct($schema, $data + $defaults, $collection, $namespace);
    }
}
