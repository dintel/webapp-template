<?php

namespace Application\JsonServer;

use Zend\View\Model\JsonModel;
use ArrayIterator;
use IteratorAggregate;

class Response implements IteratorAggregate
{
    private $data;

    public function __construct($result, Message $msg = null)
    {
        $this->data = array();

        if ($result !== null) {
            $this->data['result'] = $result;
        }

        if ($msg !== null) {
            $this->data['message'] = $msg;
        }
    }

    public function getIterator()
    {
        return new ArrayIterator($this->data);
    }

    public static function model($result, Message $msg = null)
    {
        return new JsonModel(new Response($result, $msg));
    }
}
