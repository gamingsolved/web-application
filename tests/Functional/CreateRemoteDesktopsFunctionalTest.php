<?php

namespace Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Tests\Helpers\Helpers;

class CreateRemoteDesktopsFunctionalTest extends WebTestCase
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

    public function testCreatingRemoteDesktop()
    {
        $this->resetDatabase();

        $client = $this->getClientThatRegisteredAndActivatedAUser();

        $crawler = $client->request('GET', '/en/');
        $link = $crawler->selectLink('Create new remote desktop')->link();
        $crawler = $client->click($link);

        $buttonNode = $crawler->selectButton('Continue');
        $form = $buttonNode->form();

        $client->submit($form, [
            'remote_desktop[title]' => 'My first remote desktop',
            'remote_desktop[kind]' => '0' // "Gaming"
        ]);

        $crawler = $client->request('GET', '/en/remoteDesktops/');

        $this->assertNotContains('You do not yet have any remote desktops.', $crawler->filter('body')->text());

        $this->assertContains('My first remote desktop', $crawler->filter('h2')->first()->text());

        $this->assertContains('For playing computer games', $crawler->filter('span.label-info')->first()->text());

        // Two checks due to line break
        $this->assertContains('Current status:', $crawler->filter('span.label-default')->first()->text());
        $this->assertContains('not running', $crawler->filter('span.label-default')->first()->text());

        $this->assertContains('Launch this remote desktop', $crawler->filter('a.btn')->first()->text());

        // We want to build on this in another test
        return $client;
    }

    public function testLaunchRemoteDesktop()
    {
        $client = $this->testCreatingRemoteDesktop();

        $crawler = $client->request('GET', '/en/remoteDesktops/');

        $link = $crawler->selectLink('Launch this remote desktop')->first()->link();

        $crawler = $client->click($link);

        // Verify that we wen into the new instance creation workflow
        $container = $client->getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        $repo = $em->getRepository('AppBundle\Entity\RemoteDesktop\RemoteDesktop');
        $remoteDesktop = $repo->findOneBy(['title' => 'My first remote desktop']);
        $this->assertEquals(
            '/en/remoteDesktops/' . $remoteDesktop->getId() . '/cloudInstances/new',
            $client->getRequest()->getRequestUri()
        );

        $this->assertContains('Launch your remote desktop', $crawler->filter('h1')->first()->text());
    }

}
