<?php

namespace Tests\AppBundle\Service;

use AppBundle\Coordinator\CloudInstance\CloudInstanceCoordinatorFactory;
use AppBundle\Service\CloudInstanceManagementService;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;

class CloudInstanceManagementServiceTest extends TestCase
{

    public function getMockCloudInstanceCoordinatorFactory()
    {
        return $this->getMockBuilder(CloudInstanceCoordinatorFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function getMockEntityManager()
    {
        return $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testScheduledForLauncIsLaunched()
    {
        $cloudInstanceManagementService = new CloudInstanceManagementService(
            $this->getMockEntityManager(),
            $this->getMockCloudInstanceCoordinatorFactory()
        );
        $this->assertTrue(true);
    }

}
