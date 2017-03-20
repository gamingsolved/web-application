<?php

namespace Tests\Functional;

use AppBundle\Entity\CloudInstance\CloudInstance;
use AppBundle\Entity\RemoteDesktop\RemoteDesktop;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Tests\Helpers\Helpers;

class LaunchRemoteDesktopsFunctionalTest extends WebTestCase
{
    use Helpers;

    public function testCreatingRemoteDesktopNotAvailableWhenNotLoggedIn()
    {
        $this->resetDatabase();

        $client = static::createClient();

        $client->request('GET', '/en/remoteDesktops/new');

        $this->assertEquals(302, $client->getResponse()->getStatusCode());

        $this->assertEquals('http://localhost/en/login', $client->getResponse()->headers->get('location'));
    }

    public function testLaunchRemoteDesktop()
    {
        $client = (new CreateRemoteDesktopsFunctionalTest())->testCreateRemoteDesktop();

        $crawler = $client->request('GET', '/en/remoteDesktops/');

        $link = $crawler->selectLink('Launch this remote desktop')->first()->link();

        $crawler = $client->click($link);

        // Verify that we went into the instance creation workflow
        $container = $client->getContainer();
        /** @var EntityManager $em */
        $em = $container->get('doctrine.orm.entity_manager');
        $remoteDesktopRepo = $em->getRepository('AppBundle\Entity\RemoteDesktop\RemoteDesktop');
        /** @var RemoteDesktop $remoteDesktop */
        $remoteDesktop = $remoteDesktopRepo->findOneBy(['title' => 'My first remote desktop']);
        $this->assertEquals(
            '/en/remoteDesktops/' . $remoteDesktop->getId() . '/cloudInstances/new',
            $client->getRequest()->getRequestUri()
        );

        $this->assertContains('Launch your remote desktop', $crawler->filter('h1')->first()->text());

        $buttonNode = $crawler->selectButton('Launch now');
        $form = $buttonNode->form();

        $client->submit($form, [
            'form[region]' => 'eu-central-1',
        ]);

        $crawler = $client->followRedirect();

        // We want to be back in the overview
        $this->assertEquals(
            '/en/remoteDesktops/',
            $client->getRequest()->getRequestUri()
        );

        $this->assertContains('My first remote desktop', $crawler->filter('h2')->first()->text());

        $this->assertContains('Current status:', $crawler->filter('h3')->first()->text());
        $this->assertContains('Booting...', $crawler->filter('span.label')->first()->text());

        $this->assertEquals(
            0,
            $crawler->filter('a.btn:contains("Launch this remote desktop")')->count()
        );

        $this->assertContains('Refresh status', $crawler->filter('a.remotedesktop-action-button')->first()->text());

        // Switching to "Ready to use" status

        // We must pull this again because else Doctrine is irritated (because due to the browsing, this old object is out of sync)
        $remoteDesktop = $remoteDesktopRepo->findOneBy(['title' => 'My first remote desktop']);
        /** @var CloudInstance $cloudInstance */
        $cloudInstance = $remoteDesktop->getCloudInstances()->get(0);
        $cloudInstance->setPublicAddress('121.122.123.124');
        $cloudInstance->setAdminPassword('foo');
        $cloudInstance->setRunstatus(CloudInstance::RUNSTATUS_RUNNING);
        $em->persist($cloudInstance);
        $em->flush();

        $crawler = $client->request('GET', '/en/remoteDesktops/');

        $this->assertContains('My first remote desktop', $crawler->filter('h2')->first()->text());

        $this->assertContains('Current status:', $crawler->filter('h3')->first()->text());
        $this->assertContains('Ready to use', $crawler->filter('span.label')->first()->text());
        $this->assertContains('Stop this remote desktop', $crawler->filter('a.remotedesktop-action-button')->first()->text());

        // We want to build on this in other tests
        return $client;
    }

}
