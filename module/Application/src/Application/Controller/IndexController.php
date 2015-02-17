<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Zend\EventManager\EventManagerInterface;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    protected $mapper;

    public function setEventManager(EventManagerInterface $events)
    {
        parent::setEventManager($events);
        $this->init();
        return $this;
    }

    protected function init()
    {
        $this->mapper = $this->getServiceLocator()->get('mapper');
    }

    public function indexAction()
    {
        return new ViewModel();
    }
}
