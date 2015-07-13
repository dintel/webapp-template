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
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;
use Application\Model\User;
use Application\JsonServer\Message as JsonMessage;
use Application\JsonServer\Response as JsonResponse;

class AuthController extends AbstractActionController
{
    const MIN_LOGIN_LENGTH=4;
    const MIN_PASSWORD_LENGTH=8;

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

    public function loginAction()
    {
        $auth = $this->getServiceLocator()->get('auth');
        if ($auth->hasIdentity()) {
            return $this->redirect()->toRoute('application/default', ['controller' => 'index',  'action' => 'index']);
        }

        $login = $this->params()->fromPost('login');
        $password = $this->params()->fromPost('password');
        if (isset($login, $password)) {
            $auth->getAdapter()->setCredentials($login, $password);
            $result = $auth->authenticate();
            if ($result->isValid()) {
                return $this->redirect()->toRoute('application/default', ['controller' => 'index', 'action' => 'index']);
            } else {
                $this->flashMessenger()->addErrorMessage('Invalid username or password');
            }
        }

        return new ViewModel();
    }

    public function logoutAction()
    {
        $this->getServiceLocator()->get('auth')->clearIdentity();
        $this->redirect()->toRoute('application/default', ['controller' => 'auth', 'action' => 'login']);
    }

    public function listAction()
    {
        $users = $this->mapper->fetchObjects('users', 'User', [], ['login' => 1]);
        return JsonResponse::model($users);
    }

    public function saveAction()
    {
        $data = $this->params()->fromPost('user');

        if ($data === null) {
            return JsonResponse::model(null, JsonMessage::error("Error: missing parameters"));
        }

        $data = json_decode($data, true);
        if ($data === null) {
            return JsonResponse::model(null, JsonMessage::error("Error: could not parse parameters"));
        }

        if (strlen($data['login']) < self::MIN_LOGIN_LENGTH) {
            return JsonResponse::model(null, JsonMessage::error("Login length is less than ".self::MIN_LOGIN_LENGTH));
        }

        if ($data['name'] === "") {
            return JsonResponse::model(null, JsonMessage::error("Name must not be empty"));
        }

        if ($data['email'] === "" || !strpos($data['email'], '@')) {
            return JsonResponse::model(null, JsonMessage::error("Invalid email specified"));
        }

        if (isset($data['password']) && strlen($data['password']) < self::MIN_PASSWORD_LENGTH) {
            return JsonResponse::model(null, JsonMessage::error("Password length is less than ".self::MIN_LOGIN_LENGTH));
        }

        if (isset($data['password'])) {
            $data['password'] = hash('sha512', $data['password']);
        }

        if (!isset($data['id'])) {
            $user = $this->mapper->findObjectByAttr("users", "User", "login", $data['login']);
            if ($user !== null) {
                return JsonResponse::model(null, JsonMessage::error("User with such login already exists"));
            }
            $user = $this->mapper->newObject('users', 'User', $data);
        } else {
            $user = $this->mapper->findObject('users', 'User', $data['id']);
            if ($user === null) {
                return JsonResponse::model(null, JsonMessage::error("Error: could not find user ID ".$data['id']));
            }
            unset($data['id']);
            $user->mergeData($data);
        }

        $user->save();
        return JsonResponse::model(null, JsonMessage::success("Successfully saved user '{$user->login}'"));
    }

    public function deleteAction()
    {
        $ids = $this->params()->fromPost("ids");

        if ($ids === null) {
            return JsonResponse::model(null, JsonMessage::error("Error: missing parameters"));
        }

        $ids = json_decode($ids, true);
        if ($ids === null) {
            return JsonResponse::model(null, JsonMessage::error("Error: could not parse parameters"));
        }

        if (count($ids) == 0) {
            return JsonResponse::model(null, JsonMessage::error("Error: no users to delete specified"));
        }

        $users = [];
        foreach ($ids as $id) {
            $user = $this->mapper->findObject('users', 'User', $id);
            if ($user === null) {
                return JsonResponse::model(null, JsonMessage::error("Error: user ID '{$id}' not found"));
            }
            $users[] = $user;
        }

        foreach ($users as $user) {
            $user->delete();
        }

        $count = count($users);
        return JsonResponse::model(null, JsonMessage::success("Successfully deleted {$count} users"));
    }

    public function toggleAction()
    {
        $id = $this->params()->fromPost("id");
        if (!isset($id)) {
            return JsonResponse::model(null, JsonMessage::error('Invalid ID specified'));
        }
        $user = $this->mapper->findObject('users', 'User', $id);
        if ($user === null) {
            return JsonResponse::model(null, JsonMessage::error('User not found'));
        }

        $user->active = !$user->active;
        $user->save();

        return JsonResponse::model($user);
    }

    public function initializeAction()
    {
        if ($this->mapper->countObjects("users") > 0) {
            return $this->redirect()->toRoute('application/default', ['controller' => 'auth', 'action' => 'login']);
        }

        $password = self::randomPassword();
        $data = [
            'login' => 'admin',
            'type' => User::TYPE_ADMIN,
            'name' => 'Administrator',
            'email' => 'admin@example.com',
            'password' => hash('sha512', $password),
            'active' => true,
        ];
        $user = $this->mapper->newObject('users', 'User', $data);
        $user->save();

        return new ViewModel(['user' => $user, 'password' => $password]);
    }

    private static function randomPassword($length = 12)
    {
        $alphabet = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
        $pass = array(); //remember to declare $pass as an array
        $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
        for ($i = 0; $i < $length; $i++) {
            $n = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }
        return implode($pass); //turn the array into a string
    }
}
