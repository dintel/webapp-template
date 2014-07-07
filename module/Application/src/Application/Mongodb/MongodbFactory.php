<?php
namespace Application\Mongodb;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use MongoClient;

class MongodbFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $services)
    {
        $mongoClient = new MongoClient();
        $config = $services->get('config');
        return $mongoClient->selectDb($config['mongodb']['database']);
    }
}
