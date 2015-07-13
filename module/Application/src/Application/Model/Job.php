<?php
namespace Application\Job;

use MongoObject\Object;
use MongoCollection;
use ZendJobQueue;

class Job extends Object implements MapperObject
{
    const STATUS_INITIAL='Initial';
    const STATUS_RUNNING='Running';
    const STATUS_SUCCESS='Success';
    const STATUS_FAIL='Fail';
    const STATUS_KILLED='Killed';

    protected $_id;
    protected $name;
    protected $url;
    protected $status;
    protected $parameters;
    protected $results;

    public function __construct(array $data, MongoCollection $collection)
    {
        $schema = [
            '_id' => ['type' => Object::TYPE_ID, 'null' => false],
            'name' => ['type' => Object::TYPE_STRING, 'null' => false],
            'url' => ['type' => Object::TYPE_STRING, 'null' => false],
            'status' => ['type' => Object::TYPE_STRING, 'null' => false],
            'parameters' => ['type' => Object::TYPE_ARRAY, 'null' => false],
            'results' => ['type' => Object::TYPE_ARRAY, 'null' => false],
            'log' => ['type' => Object::TYPE_ARRAY, 'null' => false],
        ];
        $defaults = [
            'parameters' => [],
            'results' => [],
            'log' => [],
        ];
        parent::__construct($schema, $data + $defaults, $collection);
    }

    public static function getCollection()
    {
        return "jobs";
    }

    public function checkStatus()
    {
        if ($this->status == self::STATUS_RUNNING && $this->jqId !== null) {
            $jq = new ZendJobQueue();
            $jqStatus = $jq->getJobStatus($this->jqId);

            if ($jqStatus === false || $jqStatus['status'] !== ZendJobQueue::STATUS_RUNNING) {
                $this->refresh();
                if ($this->status === self::STATUS_RUNNING) {
                    $this->status = self::STATUS_KILLED;
                    $this->save();
                }
            }
        }
    }

    public function start()
    {
        $jq = new ZendJobQueue();
        $jq->createHttpJob($this->url, ['jobId' => (string)$job->_id], ['name' => (string)$job->_id]);
    }
}
