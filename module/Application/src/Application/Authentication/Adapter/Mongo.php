<?php
namespace Application\Authentication\Adapter;

use Zend\Authentication\Adapter\AdapterInterface;
use Zend\Authentication\Result;
use MongoCollection;

class Mongo implements AdapterInterface
{
    protected $collection;
    protected $login;
    protected $password;

    /**
     * Iinitialize MongoDB authentication adapter
     *
     * @return void
     */
    public function __construct(MongoCollection $collection)
    {
        $this->collection = $collection;
    }

    /**
     * Set credentials that should be authenticated
     *
     * @return \Application\Authentication\Adapter
     */
    public function setCredentials($login, $password)
    {
        $this->login = $login;
        $this->password = $password;
        return $this;
    }

    /**
     * Performs an authentication attempt
     *
     * @return \Zend\Authentication\Result
     * @throws \Zend\Authentication\Adapter\Exception\ExceptionInterface
     *               If authentication cannot be performed
     */
    public function authenticate()
    {
        $hash = hash('sha512', $this->password);
        $user = $this->collection->findOne(['login' => $this->login, 'password' => $hash]);
        if ($user === null) {
            return new Result(Result::FAILURE, null, ['Invalid login and/or bad password']);
        }
        if (@!$user['active']) {
            return new Result(Result::FAILURE, null, ['Account is suspended']);
        }
        return new Result(Result::SUCCESS, $user);
    }
}
