<?php

namespace Tests\Functional;

use AppBundle\Entity\CloudInstance\CloudInstance;
use AppBundle\Entity\RemoteDesktop\RemoteDesktop;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Tests\Helpers\Helpers;

class RemoveRemoteDesktopsFunctionalTest extends WebTestCase
{
    use Helpers;

    protected function verifyDektopStatusRemoving(Client $client, Crawler $crawler)
    {
        $link = $crawler->selectLink('Refresh status')->first()->link();
        $crawler = $client->click($link);

        $this->assertContains('My first remote desktop', $crawler->filter('h2')->first()->text());

        $this->assertContains('Usage costs per hour', $crawler->filter('div.hourlyusagecostsbox')->first()->text());
        $this->assertContains('(only in status Ready to use): $1.49', $crawler->filter('div.hourlyusagecostsbox')->first()->text());

        $this->assertContains('Current storage costs per hour', $crawler->filter('div.hourlyusagecostsbox')->first()->text());
        $this->assertContains('(until desktop is removed): $0.04', $crawler->filter('div.hourlyusagecostsbox')->first()->text());

        $this->assertContains('Current status:', $crawler->filter('h3')->first()->text());
        $this->assertContains('Removing...', $crawler->filter('.remotedesktopstatus')->first()->text());

        $this->assertContains('Refresh status', $crawler->filter('.panel-footer a.btn')->first()->text());
        $this->assertEquals(
            1,
            $crawler->filter('.panel-footer a.btn')->count()
        );
    }

    public function testRemoveRemoteDesktop()
    {
        $client = (new StopRemoteDesktopFunctionalTest())->testStopRemoteDesktop();

        $crawler = $client->request('GET', '/en/remoteDesktops/');

        $link = $crawler->selectLink('Remove this remote desktop')->first()->link();

        $client->click($link);

        $crawler = $client->followRedirect();

        // We want to be back in the overview
        $this->assertEquals(
            '/en/remoteDesktops/',
            $client->getRequest()->getRequestUri()
        );


        // At this point, the instance is in "Scheduled for termination" state

        $this->verifyDektopStatusRemoving($client, $crawler);


        // Switching to "Terminating" status, which must not change the desktop status

        $container = $client->getContainer();
        /** @var EntityManager $em */
        $em = $container->get('doctrine.orm.entity_manager');
        $remoteDesktopRepo = $em->getRepository('AppBundle\Entity\RemoteDesktop\RemoteDesktop');
        /** @var RemoteDesktop $remoteDesktop */
        $remoteDesktop = $remoteDesktopRepo->findOneBy(['title' => 'My first remote desktop']);
        /** @var CloudInstance $cloudInstance */
        $cloudInstance = $remoteDesktop->getCloudInstances()->get(0);
        $cloudInstance->setRunstatus(CloudInstance::RUNSTATUS_TERMINATING);
        $em->persist($cloudInstance);
        $em->flush();

        $this->verifyDektopStatusRemoving($client, $crawler);


        // Switching instance to "Terminated" status, which must put the desktop into "Removed" status

        $remoteDesktop = $remoteDesktopRepo->findOneBy(['title' => 'My first remote desktop']);
        /** @var CloudInstance $cloudInstance */
        $cloudInstance = $remoteDesktop->getCloudInstances()->get(0);
        $cloudInstance->setRunstatus(CloudInstance::RUNSTATUS_TERMINATED);
        $em->persist($cloudInstance);
        $em->flush();

        $link = $crawler->selectLink('Refresh status')->first()->link();
        $crawler = $client->click($link);

        $this->assertEmpty($crawler->filter('h2'));
        $this->assertEmpty($crawler->filter('div.hourlyusagecostsbox'));
        $this->assertEmpty($crawler->filter('h3'));
        $this->assertEmpty($crawler->filter('.remotedesktopstatus'));

        $this->assertEquals(
            0,
            $crawler->filter('.panel-footer a.btn')->count()
        );
    }

}
