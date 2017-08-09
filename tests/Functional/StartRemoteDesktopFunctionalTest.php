<?php

namespace Tests\Functional;

use AppBundle\Entity\CloudInstance\CloudInstance;
use AppBundle\Entity\RemoteDesktop\Event\RemoteDesktopRelevantForBillingEvent;
use AppBundle\Entity\RemoteDesktop\RemoteDesktop;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Tests\Helpers\Helpers;

class StartRemoteDesktopFunctionalTest extends WebTestCase
{
    use Helpers;

    protected function verifyDektopStatusStarting(Client $client, Crawler $crawler)
    {
        $link = $crawler->selectLink('Refresh status')->first()->link();
        $crawler = $client->click($link);

        $this->assertContains('My first cloud gaming rig', $crawler->filter('h2')->first()->text());

        $this->assertContains('Current status:', $crawler->filter('h3')->first()->text());
        $this->assertContains('Booting...', $crawler->filter('.remotedesktopstatus')->first()->text());

        $this->assertEquals(
            0,
            $crawler->filter('a.btn:contains("Start this cloud gaming rig")')->count()
        );

        $this->assertContains('Refresh status', $crawler->filter('.panel-footer a.btn')->first()->text());
        $this->assertEquals(
            1,
            $crawler->filter('.panel-footer a.btn')->count()
        );
    }

    public function testStartRemoteDesktop()
    {
        $client = (new StopRemoteDesktopFunctionalTest())->testStopRemoteDesktop();

        $crawler = $client->request('GET', '/en/remoteDesktops/');

        $link = $crawler->selectLink('Start this cloud gaming rig')->first()->link();

        $client->click($link);

        $crawler = $client->followRedirect();

        // We want to be back in the overview
        $this->assertEquals(
            '/en/remoteDesktops/',
            $client->getRequest()->getRequestUri()
        );


        // At this point, the instance is in "Scheduled for start" state

        $this->verifyDektopStatusStarting($client, $crawler);


        // Switching to "Starting" status, which must not change the desktop status

        $container = $client->getContainer();
        /** @var EntityManager $em */
        $em = $container->get('doctrine.orm.entity_manager');
        $remoteDesktopRepo = $em->getRepository('AppBundle\Entity\RemoteDesktop\RemoteDesktop');
        /** @var RemoteDesktop $remoteDesktop */
        $remoteDesktop = $remoteDesktopRepo->findOneBy(['title' => 'My first cloud gaming rig']);
        /** @var CloudInstance $cloudInstance */
        $cloudInstance = $remoteDesktop->getCloudInstances()->get(0);
        $cloudInstance->setRunstatus(CloudInstance::RUNSTATUS_STARTING);
        $em->persist($cloudInstance);
        $em->flush();

        $this->verifyDektopStatusStarting($client, $crawler);


        // Switching instance to "Running" status, which must put the desktop into "Ready to use" status

        $remoteDesktop = $remoteDesktopRepo->findOneBy(['title' => 'My first cloud gaming rig']);
        /** @var CloudInstance $cloudInstance */
        $cloudInstance = $remoteDesktop->getCloudInstances()->get(0);
        $cloudInstance->setRunstatus(CloudInstance::RUNSTATUS_RUNNING);
        $em->persist($cloudInstance);
        $em->flush();

        $remoteDesktopRelevantForBillingEventRepo = $em->getRepository(RemoteDesktopRelevantForBillingEvent::class);

        /** @var RemoteDesktopRelevantForBillingEvent[] $remoteDesktopRelevantForBillingEvents */
        $remoteDesktopRelevantForBillingEvents = $remoteDesktopRelevantForBillingEventRepo->findAll();
        $this->assertEquals(
            4, // provisioning, start, stop, start
            sizeof($remoteDesktopRelevantForBillingEvents)
        );
        $this->assertEquals(
            $remoteDesktopRelevantForBillingEvents[3]->getEventType(),
            RemoteDesktopRelevantForBillingEvent::EVENT_TYPE_DESKTOP_BECAME_AVAILABLE_TO_USER
        );

        $link = $crawler->selectLink('Refresh status')->first()->link();
        $crawler = $client->click($link);

        $this->assertContains('My first cloud gaming rig', $crawler->filter('h2')->first()->text());

        $this->assertContains('Current usage costs per hour', $crawler->filter('div.hourlyusagecostsbox')->first()->text());
        $this->assertContains('(while in status Ready to use and Rebooting): $1.95', $crawler->filter('div.hourlyusagecostsbox')->first()->text());

        $this->assertContains('Current storage costs per hour', $crawler->filter('div.hourlyusagecostsbox')->first()->text());
        $this->assertContains('(until rig is removed): $0.04', $crawler->filter('div.hourlyusagecostsbox')->first()->text());

        $this->assertContains('Current status:', $crawler->filter('h3')->first()->text());
        $this->assertContains('Ready to use', $crawler->filter('.remotedesktopstatus')->first()->text());
    }

    public function testCannotStartRemoteDesktopIfBalanceTooLow()
    {
        $client = (new StopRemoteDesktopFunctionalTest())->testStopRemoteDesktop();

        $this->resetAccountBalanceForTestuser($client);

        $crawler = $client->request('GET', '/en/remoteDesktops/');

        $link = $crawler->selectLink('Start this cloud gaming rig')->first()->link();

        $crawler = $client->click($link);

        $this->assertContains(
            'Your account balance is too low to use this cloud gaming rig.',
            $crawler->filter('div.alert')->first()->text()
        );

        $this->assertContains(
            'Running this cloud gaming rig costs $1.95 per hour, but your current balance is',
            $crawler->filter('div.alert')->first()->text()
        );

        $this->assertContains(
            '$0.00.',
            $crawler->filter('div.alert')->first()->text()
        );

        $this->assertContains(
            'Click here to increase your balance now',
            $crawler->filter('a.btn')->first()->text()
        );
    }

}
