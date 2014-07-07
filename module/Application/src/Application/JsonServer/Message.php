<?php

namespace Application\JsonServer;

use ArrayIterator;
use IteratorAggregate;

class Message implements IteratorAggregate
{
    const TYPE_SUCCESS="success";
    const TYPE_INFO="info";
    const TYPE_WARNING="warning";
    const TYPE_ERROR="error";

    private $data;
    
    public function __construct($type,$text)
    {
        $this->data['type'] = $type;
        $this->data['text'] = $text;
    }

    public static function success($text)
    {
        return new Message(Message::TYPE_SUCCESS,$text);
    }

    public static function info($text)
    {
        return new Message(Message::TYPE_INFO,$text);
    }

    public static function warning($text)
    {
        return new Message(Message::TYPE_WARNING,$text);
    }

    public static function error($text)
    {
        return new Message(Message::TYPE_ERROR,$text);
    }
    
    public function getIterator()
    {
        return new ArrayIterator($this->data);
    }
}
