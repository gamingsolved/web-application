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

class StopRemoteDesktopFunctionalTest extends WebTestCase
{
    use Helpers;

    protected function verifyDektopStatusStopping(Client $client, Crawler $crawler)
    {
        $link = $crawler->selectLink('Refresh status')->first()->link();
        $crawler = $client->click($link);

        $this->assertContains('My first remote desktop', $crawler->filter('h2')->first()->text());

        $this->assertContains('Costs per hour', $crawler->filter('div.hourlycostsbox')->first()->text());
        $this->assertContains('(only in status Ready to use): $1.49', $crawler->filter('div.hourlycostsbox')->first()->text());

        $this->assertContains('Current status:', $crawler->filter('h3')->first()->text());
        $this->assertContains('Stopping...', $crawler->filter('.remotedesktopstatus')->first()->text());

        $this->assertContains('Refresh status', $crawler->filter('.panel-footer a.btn')->first()->text());
        $this->assertEquals(
            1,
            $crawler->filter('.panel-footer a.btn')->count()
        );
    }

    public function testStopRemoteDesktop()
    {
        $client = (new LaunchRemoteDesktopFunctionalTest())->testLaunchRemoteDesktop();

        $container = $client->getContainer();
        /** @var EntityManager $em */
        $em = $container->get('doctrine.orm.entity_manager');

        // Cannot directly stop an instance via the UI, only auto-stop.
        // Therefore, we do this through the entities.

        $remoteDesktopRepo = $em->getRepository('AppBundle\Entity\RemoteDesktop\RemoteDesktop');
        /** @var RemoteDesktop $remoteDesktop */
        $remoteDesktop = $remoteDesktopRepo->findOneBy(['title' => 'My first remote desktop']);
        /** @var CloudInstance $cloudInstance */
        $cloudInstance = $remoteDesktop->getCloudInstances()->get(0);
        $cloudInstance->setRunstatus(CloudInstance::RUNSTATUS_SCHEDULED_FOR_STOP);
        $em->persist($cloudInstance);
        $em->flush();

        $crawler = $client->request('GET', '/en/remoteDesktops/');


        // At this point, the instance must be in "Scheduled for stop" state on the UI

        $remoteDesktopEventRepo = $em->getRepository(RemoteDesktopEvent::class);
        $remoteDesktopEvents = $remoteDesktopEventRepo->findAll();
        $this->assertEquals(
            2, // Two, because there is one from the launch on which we build
            sizeof($remoteDesktopEvents)
        );
        /** @var RemoteDesktopEvent $remoteDesktopEvent */
        $remoteDesktopEvent = $remoteDesktopEvents[1];
        $this->assertEquals(
            $remoteDesktopEvent->getEventType(),
            RemoteDesktopEvent::EVENT_TYPE_DESKTOP_BECAME_UNAVAILABLE_TO_USER
        );

        $this->verifyDektopStatusStopping($client, $crawler);


        // Switching to "Stopping" status, which must not change the desktop status

        /** @var EntityManager $em */
        $em = $container->get('doctrine.orm.entity_manager');
        $remoteDesktopRepo = $em->getRepository('AppBundle\Entity\RemoteDesktop\RemoteDesktop');
        /** @var RemoteDesktop $remoteDesktop */
        $remoteDesktop = $remoteDesktopRepo->findOneBy(['title' => 'My first remote desktop']);
        /** @var CloudInstance $cloudInstance */
        $cloudInstance = $remoteDesktop->getCloudInstances()->get(0);
        $cloudInstance->setRunstatus(CloudInstance::RUNSTATUS_STOPPING);
        $em->persist($cloudInstance);
        $em->flush();

        $this->verifyDektopStatusStopping($client, $crawler);


        // Switching instance to "Stopped" status, which must put the desktop into "Stopped" status

        $remoteDesktop = $remoteDesktopRepo->findOneBy(['title' => 'My first remote desktop']);
        /** @var CloudInstance $cloudInstance */
        $cloudInstance = $remoteDesktop->getCloudInstances()->get(0);
        $cloudInstance->setRunstatus(CloudInstance::RUNSTATUS_STOPPED);
        $em->persist($cloudInstance);
        $em->flush();

        $link = $crawler->selectLink('Refresh status')->first()->link();
        $crawler = $client->click($link);

        $this->assertContains('My first remote desktop', $crawler->filter('h2')->first()->text());

        $this->assertContains('Costs per hour', $crawler->filter('div.hourlycostsbox')->first()->text());
        $this->assertContains('(only in status Ready to use): $1.49', $crawler->filter('div.hourlycostsbox')->first()->text());

        $this->assertContains('Current status:', $crawler->filter('h3')->first()->text());
        $this->assertContains('Stopped', $crawler->filter('.remotedesktopstatus')->first()->text());
        $this->assertContains('Start this remote desktop', $crawler->filter('a.remotedesktop-action-button')->first()->text());
        $this->assertContains('Remove this remote desktop', $crawler->filter('a.remotedesktop-action-button')->eq(1)->text());
        $this->assertContains('All your remote desktop data will be lost upon removal!', $crawler->filter('.datainfolabel')->first()->text());


        // We want to build on this in other tests
        return $client;
    }

}
