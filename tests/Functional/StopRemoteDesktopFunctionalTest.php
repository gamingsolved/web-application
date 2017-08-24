<?php

namespace Tests\Functional;

use AppBundle\Entity\CloudInstance\CloudInstance;
use AppBundle\Entity\RemoteDesktop\Event\RemoteDesktopRelevantForBillingEvent;
use AppBundle\Entity\RemoteDesktop\RemoteDesktop;
use AppBundle\Service\RemoteDesktopAutostopService;
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

        $this->assertContains('My first cloud gaming rig', $crawler->filter('h2')->first()->text());

        $this->assertContains('Usage costs per hour', $crawler->filter('div.usagecostsforoneintervalbox')->first()->text());
        $this->assertContains('(only in status Ready to use and Rebooting): $1.95', $crawler->filter('div.usagecostsforoneintervalbox')->first()->text());

        $this->assertContains('Current storage costs per hour', $crawler->filter('div.usagecostsforoneintervalbox')->first()->text());
        $this->assertContains('(until rig is removed): $0.04', $crawler->filter('div.usagecostsforoneintervalbox')->first()->text());

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

        $remoteDesktopRepo = $em->getRepository('AppBundle\Entity\RemoteDesktop\RemoteDesktop');
        /** @var RemoteDesktop $remoteDesktop */
        $remoteDesktop = $remoteDesktopRepo->findOneBy(['title' => 'My first cloud gaming rig']);

        $rdas = new RemoteDesktopAutostopService();
        $optimalHourlyAutostopTimesForRemoteDesktop = $rdas->getOptimalHourlyAutostopTimesForRemoteDesktop(
            $remoteDesktop,
            $em->getRepository(RemoteDesktopRelevantForBillingEvent::class)
        );

        // Auto-stop / Cost protection test
        $crawler = $client->request('GET', '/en/remoteDesktops/');

        for ($i = 0; $i < 7; $i++) {
            $link = $crawler->filter('.costprotectionblock a.btn')->eq($i)->link();
            $client->click($link);
            $crawler = $client->followRedirect();
            $this->assertContains(
                $optimalHourlyAutostopTimesForRemoteDesktop[$i]->format('F j, Y H:i:s') . ' (UTC)',
                $crawler->filter('.costprotectionblock p')->first()->text()
            );
        }

        // Cannot directly stop an instance via the UI, only auto-stop.
        // Therefore, we do this through the entities.
        $remoteDesktopRepo = $em->getRepository('AppBundle\Entity\RemoteDesktop\RemoteDesktop');
        /** @var RemoteDesktop $remoteDesktop */
        $remoteDesktop = $remoteDesktopRepo->findOneBy(['title' => 'My first cloud gaming rig']);
        /** @var CloudInstance $cloudInstance */
        $cloudInstance = $remoteDesktop->getCloudInstances()->get(0);
        $cloudInstance->setRunstatus(CloudInstance::RUNSTATUS_SCHEDULED_FOR_STOP);
        $em->persist($cloudInstance);
        $em->flush();

        $crawler = $client->request('GET', '/en/remoteDesktops/');

        // At this point, the instance must be in "Scheduled for stop" state on the UI

        $remoteDesktopRelevantForBillingEventRepo = $em->getRepository(RemoteDesktopRelevantForBillingEvent::class);

        /** @var RemoteDesktopRelevantForBillingEvent[] $remoteDesktopRelevantForBillingEvents */
        $remoteDesktopRelevantForBillingEvents = $remoteDesktopRelevantForBillingEventRepo->findAll();
        $this->assertEquals(
            3, // provisioned, start, stop
            sizeof($remoteDesktopRelevantForBillingEvents)
        );

        $this->assertEquals(
            $remoteDesktopRelevantForBillingEvents[2]->getEventType(),
            RemoteDesktopRelevantForBillingEvent::EVENT_TYPE_DESKTOP_BECAME_UNAVAILABLE_TO_USER
        );

        $this->verifyDektopStatusStopping($client, $crawler);


        // Switching to "Stopping" status, which must not change the desktop status

        /** @var EntityManager $em */
        $em = $container->get('doctrine.orm.entity_manager');
        $remoteDesktopRepo = $em->getRepository('AppBundle\Entity\RemoteDesktop\RemoteDesktop');
        /** @var RemoteDesktop $remoteDesktop */
        $remoteDesktop = $remoteDesktopRepo->findOneBy(['title' => 'My first cloud gaming rig']);
        /** @var CloudInstance $cloudInstance */
        $cloudInstance = $remoteDesktop->getCloudInstances()->get(0);
        $cloudInstance->setRunstatus(CloudInstance::RUNSTATUS_STOPPING);
        $em->persist($cloudInstance);
        $em->flush();

        $this->verifyDektopStatusStopping($client, $crawler);


        // Switching instance to "Stopped" status, which must put the desktop into "Stopped" status

        $remoteDesktop = $remoteDesktopRepo->findOneBy(['title' => 'My first cloud gaming rig']);
        /** @var CloudInstance $cloudInstance */
        $cloudInstance = $remoteDesktop->getCloudInstances()->get(0);
        $cloudInstance->setRunstatus(CloudInstance::RUNSTATUS_STOPPED);
        $em->persist($cloudInstance);
        $em->flush();

        $link = $crawler->selectLink('Refresh status')->first()->link();
        $crawler = $client->click($link);

        $this->assertContains('My first cloud gaming rig', $crawler->filter('h2')->first()->text());

        $this->assertContains('Usage costs per hour', $crawler->filter('div.usagecostsforoneintervalbox')->first()->text());
        $this->assertContains('(only in status Ready to use and Rebooting): $1.95', $crawler->filter('div.usagecostsforoneintervalbox')->first()->text());

        $this->assertContains('Current storage costs per hour', $crawler->filter('div.usagecostsforoneintervalbox')->first()->text());
        $this->assertContains('(until rig is removed): $0.04', $crawler->filter('div.usagecostsforoneintervalbox')->first()->text());

        $this->assertContains('Current status:', $crawler->filter('h3')->first()->text());
        $this->assertContains('Stopped', $crawler->filter('.remotedesktopstatus')->first()->text());
        $this->assertContains('Start this cloud gaming rig', $crawler->filter('a.remotedesktop-action-button')->first()->text());
        $this->assertContains('Remove this cloud gaming rig', $crawler->filter('a.remotedesktop-action-button')->eq(1)->text());
        $this->assertContains('All your cloud gaming rig data will be lost upon removal!', $crawler->filter('.datainfolabel')->first()->text());


        // We want to build on this in other tests
        return $client;
    }

}
