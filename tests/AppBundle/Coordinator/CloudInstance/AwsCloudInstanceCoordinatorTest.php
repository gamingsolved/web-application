<?php

namespace Tests\AppBundle\Coordinator\CloudInstance;

use AppBundle\Coordinator\CloudInstance\AwsCloudInstanceCoordinator;
use AppBundle\Entity\CloudInstance\AwsCloudInstance;
use AppBundle\Entity\CloudInstanceProvider\AwsCloudInstanceProvider;
use AppBundle\Entity\CloudInstanceProvider\ProviderElement\Flavor;
use AppBundle\Entity\CloudInstanceProvider\ProviderElement\Image;
use AppBundle\Entity\CloudInstanceProvider\ProviderElement\Region;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tests\Fixtures\DummyOutput;

class MockEc2Client
{
    public function runInstances(array $arr) {}
}

class AwsCloudInstanceCoordinatorTest extends TestCase
{
    public function testTriggerLaunchOfCloudInstance()
    {
        $expectedParameters = [
            'ImageId' => 'ami-14c0107b',
            'MinCount' => 1,
            'MaxCount' => 1,
            'InstanceType' => 'g2.2xlarge',
            'KeyName' => 'ubiqmachine-default',
            'SecurityGroups' => ['ubiqmachine-cgxclient-default']
        ];

        $mockResult = [
            'Instances' => [
                0 => [
                    'InstanceId' => 'i-123456'
                ]
            ]
        ];

        $mockEc2Client = $this
            ->getMockBuilder(MockEc2Client::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mockEc2Client->expects($this->once())
            ->method('runInstances')
            ->with($expectedParameters)
            ->willReturn($mockResult);

        $awsCloudInstanceProvider = new AwsCloudInstanceProvider();

        $awsCloudInstanceCoordinator = new AwsCloudInstanceCoordinator(
            ['keypairPrivateKey' => 'blubb'],
            new Region($awsCloudInstanceProvider, 'region-foo', 'Region Foo'),
            new DummyOutput(),
            $mockEc2Client
        );

        $mockCloudInstance = $this
            ->getMockBuilder(AwsCloudInstance::class)
            ->setMethods(['getId'])
            ->getMock();

        $mockCloudInstance->expects($this->exactly(2))
            ->method('getId')
            ->willReturn('abcdef');

        $mockCloudInstance->setFlavor(new Flavor($awsCloudInstanceProvider, 'g2.2xlarge', ''));
        $mockCloudInstance->setImage(new Image($awsCloudInstanceProvider, 'ami-14c0107b', ''));

        $awsCloudInstanceCoordinator->triggerLaunchOfCloudInstance($mockCloudInstance);
        $awsCloudInstanceCoordinator->updateCloudInstanceWithProviderSpecificInfoAfterLaunchWasTriggered($mockCloudInstance);

        $this->assertSame('i-123456', $mockCloudInstance->getEc2InstanceId());
    }

}
