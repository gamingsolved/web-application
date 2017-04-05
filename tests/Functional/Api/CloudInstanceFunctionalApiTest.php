<?php

namespace Tests\Functional\Api;

use AppBundle\Entity\CloudInstance\AwsCloudInstance;
use AppBundle\Entity\RemoteDesktop\RemoteDesktop;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Tests\Functional\LaunchRemoteDesktopFunctionalTest;

class CreateRemoteDesktopFunctionalTest extends WebTestCase
{
    public function testRemainingTtl()
    {
        $client = (new LaunchRemoteDesktopFunctionalTest())->testLaunchRemoteDesktop();

        $container = $client->getContainer();
        /** @var EntityManager $em */
        $em = $container->get('doctrine.orm.entity_manager');

        $remoteDesktopRepository = $em->getRepository(RemoteDesktop::class);
        /** @var RemoteDesktop $remoteDesktop */
        $remoteDesktop = $remoteDesktopRepository->findOneBy(['title' => 'My first remote desktop']);

        /** @var AwsCloudInstance $cloudInstance */
        $cloudInstance = $remoteDesktop->getActiveCloudInstance();

        $cloudInstance->setEc2InstanceId('i-1234abc');
        $em->persist($cloudInstance);
        $em->flush();

        $client->request(
            'GET',
            '/api/cloudInstances/remainingTtl?cloudInstanceProvider=aws&providerInstanceId=i-1234abc'
        );

        $this->assertSame('14340', $client->getResponse()->getContent());
    }
}
