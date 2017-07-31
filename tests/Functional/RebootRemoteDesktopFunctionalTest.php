<?php

namespace Tests\Functional;

use AppBundle\Entity\CloudInstance\CloudInstance;
use AppBundle\Entity\RemoteDesktop\Event\RemoteDesktopEvent;
use AppBundle\Entity\RemoteDesktop\RemoteDesktop;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Tests\Helpers\Helpers;

class RebootRemoteDesktopFunctionalTest extends WebTestCase
{
    use Helpers;

    protected function verifyDektopStatusRebooting(Client $client, Crawler $crawler)
    {
        $crawler = $client->request('GET', '/en/remoteDesktops/');
        $this->assertContains('My first cloud gaming rig', $crawler->filter('h2')->first()->text());

        $this->assertContains('Usage costs per hour', $crawler->filter('div.hourlyusagecostsbox')->first()->text());
        $this->assertContains('(only in status Ready to use and Rebooting): $1.95', $crawler->filter('div.hourlyusagecostsbox')->first()->text());

        $this->assertContains('Current storage costs per hour', $crawler->filter('div.hourlyusagecostsbox')->first()->text());
        $this->assertContains('(until rig is removed): $0.04', $crawler->filter('div.hourlyusagecostsbox')->first()->text());

        $this->assertContains('Current status:', $crawler->filter('h3')->first()->text());
        $this->assertContains('Rebooting', $crawler->filter('.remotedesktopstatus')->first()->text());
    }

    public function testRebootRemoteDesktop()
    {
        $client = (new LaunchRemoteDesktopFunctionalTest())->testLaunchRemoteDesktop();

        $container = $client->getContainer();
        /** @var EntityManager $em */
        $em = $container->get('doctrine.orm.entity_manager');

        $remoteDesktopRepo = $em->getRepository('AppBundle\Entity\RemoteDesktop\RemoteDesktop');
        /** @var RemoteDesktop $remoteDesktop */
        $remoteDesktop = $remoteDesktopRepo->findOneBy(['title' => 'My first cloud gaming rig']);

        $crawler = $client->request('GET', '/en/remoteDesktops/');

        $link = $crawler->selectLink('Reboot this cloud gaming rig')->first()->link();
        $crawler = $client->click($link);

        // At this point, the instance must be in "Scheduled for reboot" state on the UI

        $remoteDesktopEventRepo = $em->getRepository(RemoteDesktopEvent::class);

        /** @var RemoteDesktopEvent[] $remoteDesktopEvents */
        $remoteDesktopEvents = $remoteDesktopEventRepo->findAll();
        $this->assertEquals(
            2, // provisioned, start
            sizeof($remoteDesktopEvents)
        );

        $this->verifyDektopStatusRebooting($client, $crawler);


        // Switching to "Rebooting" status, which must not change the desktop status

        /** @var EntityManager $em */
        $em = $container->get('doctrine.orm.entity_manager');
        $remoteDesktopRepo = $em->getRepository('AppBundle\Entity\RemoteDesktop\RemoteDesktop');
        /** @var RemoteDesktop $remoteDesktop */
        $remoteDesktop = $remoteDesktopRepo->findOneBy(['title' => 'My first cloud gaming rig']);
        /** @var CloudInstance $cloudInstance */
        $cloudInstance = $remoteDesktop->getCloudInstances()->get(0);
        $cloudInstance->setRunstatus(CloudInstance::RUNSTATUS_REBOOTING);
        $em->persist($cloudInstance);
        $em->flush();

        $this->verifyDektopStatusRebooting($client, $crawler);
    }

}
