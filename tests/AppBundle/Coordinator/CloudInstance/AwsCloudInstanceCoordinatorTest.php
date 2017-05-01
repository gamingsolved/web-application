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
    protected function getMockEc2Client() : \PHPUnit_Framework_MockObject_MockObject
    {
        return $this
            ->getMockBuilder(MockEc2Client::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function getAwsCloudInstanceCoordinator(DummyOutput $dummyOutput, \PHPUnit_Framework_MockObject_MockObject $mockEc2Client)
    {
        $awsCloudInstanceProvider = new AwsCloudInstanceProvider();

        return new AwsCloudInstanceCoordinator(
            ['keypairPrivateKey' => 'blubb'],
            new Region($awsCloudInstanceProvider, 'region-foo', 'Region Foo'),
            $dummyOutput,
            $mockEc2Client
        );

    }

    protected function getMockCloudInstance() : \PHPUnit_Framework_MockObject_MockObject
    {
        $awsCloudInstanceProvider = new AwsCloudInstanceProvider();

        $mockCloudInstance = $this
            ->getMockBuilder(AwsCloudInstance::class)
            ->setMethods(['getId', 'getAdditionalVolumeSize'])
            ->getMock();

        $mockCloudInstance->expects($this->exactly(2))
            ->method('getId')
            ->willReturn('abcdef');

        $mockCloudInstance->setFlavor($awsCloudInstanceProvider->getFlavorByInternalName('g2.2xlarge'));
        $mockCloudInstance->setImage($awsCloudInstanceProvider->getImageByInternalName('ami-14c0107b'));

        return $mockCloudInstance;
    }

    public function testTriggerLaunchOfCloudInstanceWithoutAdditionalVolumesShouldGoWellIfAllIsWell()
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

        $mockEc2Client = $this->getMockEc2Client();

        $mockEc2Client->expects($this->once())
            ->method('runInstances')
            ->with($expectedParameters)
            ->willReturn($mockResult);

        $dummyOutput = new DummyOutput();

        $awsCloudInstanceCoordinator = $this->getAwsCloudInstanceCoordinator($dummyOutput, $mockEc2Client);

        $mockCloudInstance = $this->getMockCloudInstance();
        $mockCloudInstance->expects($this->exactly(1))
            ->method('getAdditionalVolumeSize')
            ->willReturn(0);

        $awsCloudInstanceCoordinator->triggerLaunchOfCloudInstance($mockCloudInstance);
        $awsCloudInstanceCoordinator->updateCloudInstanceWithProviderSpecificInfoAfterLaunchWasTriggered($mockCloudInstance);

        $this->assertSame('i-123456', $mockCloudInstance->getEc2InstanceId());
    }

    public function testTriggerLaunchOfCloudInstanceWithAdditionalVolumesShouldGoWellIfAllIsWell()
    {
        $expectedParameters = [
            'ImageId' => 'ami-14c0107b',
            'MinCount' => 1,
            'MaxCount' => 1,
            'InstanceType' => 'g2.2xlarge',
            'KeyName' => 'ubiqmachine-default',
            'SecurityGroups' => ['ubiqmachine-cgxclient-default'],
            'BlockDeviceMappings' => [
                0 => [
                    'DeviceName' => 'xvdh',
                    'Ebs' => [
                        'DeleteOnTermination' => true,
                        'Encrypted' => false,
                        'VolumeType' => 'gp2',
                        'VolumeSize' => 200
                    ]
                ]
            ]
        ];

        $mockResult = [
            'Instances' => [
                0 => [
                    'InstanceId' => 'i-123456'
                ]
            ]
        ];

        $mockEc2Client = $this->getMockEc2Client();

        $mockEc2Client->expects($this->once())
            ->method('runInstances')
            ->with($expectedParameters)
            ->willReturn($mockResult);

        $dummyOutput = new DummyOutput();

        $awsCloudInstanceCoordinator = $this->getAwsCloudInstanceCoordinator($dummyOutput, $mockEc2Client);

        $mockCloudInstance = $this->getMockCloudInstance();

        $mockCloudInstance->expects($this->exactly(2))
            ->method('getAdditionalVolumeSize')
            ->willReturn(200);

        $awsCloudInstanceCoordinator->triggerLaunchOfCloudInstance($mockCloudInstance);
        $awsCloudInstanceCoordinator->updateCloudInstanceWithProviderSpecificInfoAfterLaunchWasTriggered($mockCloudInstance);

        $this->assertSame('i-123456', $mockCloudInstance->getEc2InstanceId());
    }

}
