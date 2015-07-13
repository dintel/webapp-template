<?php
namespace Application\Job;

use Application\Model\Job;
use ZendJobQueue;

class Scheduler
{
    protected $jq;

    public function __construct()
    {
        $this->jq = new ZendJobQueue();
    }

    public function start(Job $job)
    {
        $jq = new ZendJobQueue();
        $jq->createHttpJob($job->getUrl(), ['jobId' => $job->getId()], ['name' => $job->getId()]);
    }
}
