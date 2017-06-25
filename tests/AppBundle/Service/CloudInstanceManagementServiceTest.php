<?php

namespace Tests\AppBundle\Service;

use AppBundle\Coordinator\CloudInstance\CloudInstanceCoordinatorFactory;
use AppBundle\Entity\Billing\AccountMovement;
use AppBundle\Entity\Billing\AccountMovementRepository;
use AppBundle\Entity\CloudInstance\AwsCloudInstance;
use AppBundle\Entity\CloudInstance\CloudInstance;
use AppBundle\Entity\CloudInstanceProvider\AwsCloudInstanceProvider;
use AppBundle\Entity\CloudInstanceProvider\ProviderElement\Flavor;
use AppBundle\Entity\RemoteDesktop\RemoteDesktop;
use AppBundle\Entity\RemoteDesktop\RemoteDesktopKind;
use AppBundle\Entity\User;
use AppBundle\Service\CloudInstanceManagementService;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Tests\Fixtures\DummyOutput;

class CloudInstanceManagementServiceTest extends TestCase
{

    private function getMockCloudInstanceCoordinatorFactory()
    {
        return $this->getMockBuilder(CloudInstanceCoordinatorFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function getMockEntityManager()
    {
        return $this->getMockBuilder(EntityManager::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function getMockAccountMovementRepository()
    {
        return $this->getMockBuilder(AccountMovementRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function getUser(): User
    {
        $user = new User();
        $user->setUsername('userA');

        return $user;
    }

    private function getRemoteDesktop(User $user): RemoteDesktop
    {
        $remoteDesktop = new RemoteDesktop();
        $remoteDesktop->setId('r1');
        $remoteDesktop->setCloudInstanceProvider(new AwsCloudInstanceProvider());
        $remoteDesktop->setKind(RemoteDesktopKind::createRemoteDesktopKind(RemoteDesktopKind::GAMING_PRO));
        $remoteDesktop->setUser($user);

        return $remoteDesktop;
    }

    private function getCloudInstance(RemoteDesktop $remoteDesktop) : CloudInstance
    {
        $awsCloudInstanceProvider = new AwsCloudInstanceProvider();
        $cloudInstance = new AwsCloudInstance();
        $cloudInstance->setId('c1');
        $cloudInstance->setEc2InstanceId('ec1');
        $cloudInstance->setRemoteDesktop($remoteDesktop);
        $cloudInstance->setFlavor($awsCloudInstanceProvider->getFlavorByInternalName('g2.2xlarge'));
        $cloudInstance->setImage($awsCloudInstanceProvider->getImageByInternalName('ami-f2fde69e'));
        $cloudInstance->setRegion($awsCloudInstanceProvider->getRegionByInternalName('eu-central-1'));

        return $cloudInstance;
    }

    private function getInput() : ArrayInput
    {
        return new ArrayInput(
            [
                'awsApiKey' => 'foo',
                'awsApiSecret' => 'bar',
                'awsKeypairPrivateKeyFile' => 'baz'
            ],
            new InputDefinition([
                new InputArgument('awsApiKey', InputArgument::REQUIRED),
                new InputArgument('awsApiSecret', InputArgument::REQUIRED),
                new InputArgument('awsKeypairPrivateKeyFile', InputArgument::REQUIRED),
            ])
        );
    }

    public function testScheduledForLaunchIsNotLaunchedIfBalanceInsufficient()
    {
        $user = $this->getUser();
        $remoteDesktop = $this->getRemoteDesktop($user);
        $cloudInstance = $this->getCloudInstance($remoteDesktop);
        $input = $this->getInput();
        $output = new BufferedOutput();


        $mockAccountMovementRepository = $this->getMockAccountMovementRepository();

        $mockAccountMovementRepository
            ->expects($this->once())
            ->method('getAccountBalanceForUser')
            ->with($user)
            ->willReturn(0.0);


        $mockEm = $this->getMockEntityManager();

        $mockEm
            ->expects($this->once())
            ->method('getRepository')
            ->with(AccountMovement::class)
            ->willReturn($mockAccountMovementRepository);


        $cloudInstanceManagementService = new CloudInstanceManagementService(
            $mockEm,
            $this->getMockCloudInstanceCoordinatorFactory()
        );


        $cloudInstance->setRunstatus(CloudInstance::RUNSTATUS_SCHEDULED_FOR_LAUNCH);

        $cloudInstanceManagementService->manageCloudInstance($cloudInstance, $input, $output);


        $loglines = $output->fetch();

        $this->assertSame(CloudInstance::RUNSTATUS_SCHEDULED_FOR_LAUNCH, $cloudInstance->getRunstatus());
        $this->assertContains('Action: would launch the cloud instance, but owner has insufficient balance', $loglines);
        $this->assertContains('Hourly costs would be 1.49, balance is only 0', $loglines);
    }

}
