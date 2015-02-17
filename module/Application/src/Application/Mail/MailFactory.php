<?php
namespace Application\Mail;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class MailFactory implements FactoryInterface
{
    public function createService(ServiceLocatorInterface $services)
    {
        $config = $services->get('config');
        return new Mail($services->get('ViewRenderer'), $config['mail']['from']);
    }
}
