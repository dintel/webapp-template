<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application;

use Zend\Log\Logger;
use Zend\Log\Writer\Stream as StreamLogWriter;
use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Router\Http\RouteMatch;
use Zend\ServiceManager\ServiceManager;
use Zend\Authentication\AuthenticationService;
use Application\Authentication\Adapter\Mongo as AuthAdapter;

class Module
{
    public function onBootstrap(MvcEvent $e)
    {
        $eventManager        = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);

        /* Initialize services */
        $this->initServices($e->getApplication()->getServiceManager());

        /* Add MVC events custom handlers */
        $e->getApplication()->getEventManager()->attach(MvcEvent::EVENT_ROUTE,[$this,'onRoute']);
        $e->getApplication()->getEventManager()->attach(MvcEvent::EVENT_DISPATCH,[$this,'onDispatch']);
    }

    protected function initServices(ServiceManager $sm)
    {
        // Initialize log service
        $logger = new Logger();
        $writer = new StreamLogWriter(__DIR__.'/../../data/log/template.log');
        $logger->addWriter($writer);
        Logger::registerErrorHandler($logger);
        $sm->setService('log',$logger);

        // Initialize authentication service
        $mongodb = $sm->get('mongodb');
        $authAdapter = new AuthAdapter($mongodb->users);
        $auth = new AuthenticationService();
        $auth->setAdapter($authAdapter);
        $sm->setService('auth',$auth);
    }

    public function onRoute(MvcEvent $e)
    {
        $match = $e->getRouteMatch();
        $auth = $e->getApplication()->getServiceManager()->get('auth');
        if(!$auth->hasIdentity()) {
            if(!$this->isPublicRoute($e)) {
                $match->setParam('controller','Application\Controller\Auth');
                $match->setParam('action','login');
            }
        } else {
            $e->getViewModel()->user = $auth->getIdentity();
            $e->getViewModel()->controller = strtolower(substr(strrchr($match->getParam('controller'),'\\'),1));
            $e->getViewModel()->action = $match->getParam('action');
        }
    }

    public function isPublicRoute(MvcEvent $e)
    {
        $match = $e->getRouteMatch();
        $publicRoutes = $e->getApplication()->getServiceManager()->get('config')["public_routes"];
        $currentRoute = ['controller' => $match->getParam('controller'), 'action' => $match->getParam('action')];
        foreach($publicRoutes as $route) {
            if($currentRoute['controller'] == $route['controller'] && ($route['action'] === null || $currentRoute['action'] == $route['action'])) {
                return true;
            }
        }
        return false;
    }

    public function onDispatch(MvcEvent $e)
    {
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }
}
