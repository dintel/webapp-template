<?php
namespace Application\Job;

interface Job
{

    public function getStatus();
    public function setStatus($status);

    public function getId();
    public function getUrl();
    public function getParameters();

    public function save();
}
