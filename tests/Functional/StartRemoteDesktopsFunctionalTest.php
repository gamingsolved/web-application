<?php

namespace Tests\Functional;

use AppBundle\Entity\CloudInstance\CloudInstance;
use AppBundle\Entity\RemoteDesktop\RemoteDesktop;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Tests\Helpers\Helpers;

class StartRemoteDesktopsFunctionalTest extends WebTestCase
{
    use Helpers;

    protected function verifyDektopStatusStarting(Client $client, Crawler $crawler)
    {
        $link = $crawler->selectLink('Refresh status')->first()->link();
        $crawler = $client->click($link);

        $this->assertContains('My first remote desktop', $crawler->filter('h2')->first()->text());

        $this->assertContains('Current status:', $crawler->filter('h3')->first()->text());
        $this->assertContains('Booting...', $crawler->filter('span.label')->first()->text());

        $this->assertEquals(
            0,
            $crawler->filter('a.btn:contains("Start this remote desktop")')->count()
        );
    }

    public function testStartRemoteDesktop()
    {
        $client = (new StopRemoteDesktopsFunctionalTest())->testStopRemoteDesktop();

        $crawler = $client->request('GET', '/en/remoteDesktops/');

        $link = $crawler->selectLink('Start this remote desktop')->first()->link();

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
        $remoteDesktop = $remoteDesktopRepo->findOneBy(['title' => 'My first remote desktop']);
        /** @var CloudInstance $cloudInstance */
        $cloudInstance = $remoteDesktop->getCloudInstances()->get(0);
        $cloudInstance->setRunstatus(CloudInstance::RUNSTATUS_STARTING);
        $em->persist($cloudInstance);
        $em->flush();

        $this->verifyDektopStatusStarting($client, $crawler);


        // Switching instance to "Running" status, which must put the desktop into "Ready to use" status

        $remoteDesktop = $remoteDesktopRepo->findOneBy(['title' => 'My first remote desktop']);
        /** @var CloudInstance $cloudInstance */
        $cloudInstance = $remoteDesktop->getCloudInstances()->get(0);
        $cloudInstance->setRunstatus(CloudInstance::RUNSTATUS_RUNNING);
        $em->persist($cloudInstance);
        $em->flush();

        $link = $crawler->selectLink('Refresh status')->first()->link();
        $crawler = $client->click($link);

        $this->assertContains('My first remote desktop', $crawler->filter('h2')->first()->text());

        $this->assertContains('Current status:', $crawler->filter('h3')->first()->text());
        $this->assertContains('Ready to use', $crawler->filter('span.label')->first()->text());
        $this->assertContains('Stop this remote desktop', $crawler->filter('a.remotedesktop-action-button')->first()->text());
    }

}
