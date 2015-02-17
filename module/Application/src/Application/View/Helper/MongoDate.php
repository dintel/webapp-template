<?php
namespace Application\View\Helper;

use Zend\View\Helper\AbstractHelper;
use MongoDate;

class MongoDate extends AbstractHelper
{
    public function __invoke($date)
    {
        if ($date instanceof MongoDate)
            return date("Y-m-d H:i:s", $date->sec);
        return "-";
    }
}
