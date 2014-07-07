<?php
namespace Application\Ec2;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Aws\Common\Enum\Region;
use Aws\Ec2\Ec2Client;
use Guzzle\Plugin\Log\LogPlugin;

class Ec2 implements FactoryInterface
{
    private $config;
    private $ec2;
    private $log;
    private $lastError;

    public function createService(ServiceLocatorInterface $services)
    {
        $config = $services->get('Config');
        if(!isset($config['ec2'])) {
            throw new \Exception('AWS EC2 configuration not found');
        }
        $this->config = $config['ec2'];
        $this->log = $services->get('log');
        $this->ec2 = Ec2Client::factory($this->config);
        $this->ec2->addSubscriber(LogPlugin::getDebugPlugin(true,fopen(__DIR__."/../../../../../var/log/ec2.log",'a')));
        $this->key = $this->config['key'];
        $this->secret = $this->config['secret'];
        $this->lastError = "";
        $this->log->debug("Initialized EC2 client");
        return $this;
    }
    
    public function shareAmi($ami,array $accounts)
    {
        $add = array();
        foreach($accounts as $acc) {
            $add[] = [ 'UserId' => $acc ];
        }

        try {
            $result = $this->ec2->describeImages(['ImageIds' => [$ami]]);
            $snapshotId = $result['Images'][0]['BlockDeviceMappings'][0]['Ebs']['SnapshotId'];
            $kernelId = $result['Images'][0]['KernelId'];
            $this->ec2->modifyImageAttribute(['ImageId' => $ami, 'LaunchPermission' => [ 'Add' => $add ]]);
            $this->ec2->modifySnapshotAttribute([
                'SnapshotId' => $snapshotId,
                'Attribute' => 'createVolumePermission',
                'OperationType' => 'add',
                'UserIds' => $accounts,
                'CreateVolumePermission' => [
                    'Add' => $add,
                ],
            ]);
        } catch(Aws\Ec2\Exception\Ec2Exception $e) {
            $this->log->debug("AWS EC2 Exception: $e");
        }
        return $kernelId;
    }
    
    public function deleteAmi($ami)
    {
        try {
            $result = $this->ec2->describeImages(['ImageIds' => [$ami]]);
            $snapshotId = $result['Images'][0]['BlockDeviceMappings'][0]['Ebs']['SnapshotId'];
            $this->ec2->deregisterImage(['ImageId' => $ami]);
            $this->ec2->deleteSnapshot(['SnapshotId' => $snapshotId]);
        } catch(\Aws\Ec2\Exception\Ec2Exception $e) {
            $this->log->debug("AWS EC2 Exception: $e");
        }
    }

    public function getAmiName($ami)
    {
        try {
            $result = $this->ec2->describeImages(['ImageIds' => [$ami]]);
            return $result['Images'][0]['Name'];
        } catch(\Aws\Ec2\Exception\Ec2Exception $e) {
            $this->log->debug("AWS EC2 Exception: $e");
        }
    }
    
    public function findAmiByPidAndName($pid,$name)
    {
        $regions = [
            Region::US_EAST_1,
            Region::US_WEST_1,
            Region::US_WEST_2,
            Region::SA_EAST_1,
            Region::EU_WEST_1,
            Region::AP_SOUTHEAST_1,
            Region::AP_SOUTHEAST_2,
            Region::AP_NORTHEAST_1,
        ];
        $filters = [
            ['Name' => 'product-code', 'Values' => [$pid]],
        ];
        $length = strlen($name);
        $result = array();

        $this->ec2->addSubscriber(LogPlugin::getDebugPlugin(true,$this->log));
        foreach($regions as $region) {
            $ec2 = Ec2Client::factory(['region' => $region, 'scheme' => 'http', 'key' => $this->key, 'secret' => $this->secret]);
            try {
                $ec2Result = $ec2->describeImages(['Filters' => $filters]);
                $this->log->debug(count($ec2Result['Images']). ' images fetched');
                foreach($ec2Result['Images'] as $image) {
                    if(strncmp($name,$image['Name'],$length) === 0)
                        $result[$region] = $image['ImageId'];
                }
            } catch(\Aws\Ec2\Exception\Ec2Exception $e) {
                $this->log->debug("AWS EC2 Exception: $e");
            }
        }

        return $result;
    }

    public function getInstances(array $tags, array $amis = null)
    {
        $filters = [ ['Name' => 'tag-key', 'Values' => $tags, ['Name' => 'tag-value', 'Values' => ['true']] ];
        if($amis !== null) {
            $filters[] = ['Name' => 'image-id', 'Values' => $amis];
        }
        $result = [];

        try {
            $reservations = $this->ec2->describeInstances(['Filters' => $filters]);
            foreach($reservations['Reservations'] as $reservation) {
                foreach($reservation['Instances'] as $instance) {
                    $name = "";
                    foreach($instance['Tags'] as $tag) {
                        if($tag['Key'] == 'Name') {
                            $name = $tag['Value'];
                        }
                    }
                    $result[] = [
                        'id' => $instance['InstanceId'],
                        'ami' => $instance['ImageId'],
                        'ip' => $instance['PublicIpAddress'] === null ? '' : $instance['PublicIpAddress'],
                        'name' => $name,
                        'status' => $instance['State']['Name'],
                    ];
                }
            }
        } catch(Aws\Ec2\Exception\Ec2Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
            
        return $result;
    }

    public function runInstance($ami,$name,array $tags = null)
    {
        try {
            $result = $this->ec2->runInstances([
                'ImageId' => $ami,
                'MinCount' => 1,
                'MaxCount' => 1,
                'KeyName' => $this->config['key_name'],
                'SecurityGroups' => [ $this->config['security_group'] ],
                'InstanceType' => $this->config['instance_type'],
            ]);
            $instanceId = $result['Instances'][0]['InstanceId'];
            $result = $this->ec2->createTags(['Resources' => [$instanceId], 'Tags' => [['Key' => 'Name', 'Value' => $name],['Key' => 'AMI-Builder', 'Value' => 'true'],]]);
        } catch(Aws\Ec2\Exception\Ec2Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
        return true;
    }

    public function terminateInstances(array $instances)
    {
        try {
            $this->ec2->terminateInstances(['InstanceIds' => $instances]);
        } catch(Aws\Ec2\Exception\Ec2Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
        return true;
    }
    
    public function getLastError()
    {
        return $this->lastError;
    }
}
