<?php

namespace Tests\Functional;

use AppBundle\Entity\RemoteDesktop\RemoteDesktop;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Tests\Helpers\Helpers;

class CreateRemoteDesktopFunctionalTest extends WebTestCase
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

    public function testCreateRemoteDesktop()
    {
        $this->resetDatabase();

        $client = $this->getClientThatRegisteredAndActivatedAUser();

        $crawler = $client->request('GET', '/en/remoteDesktops/');
        $link = $crawler->selectLink('Create a new remote desktop')->first()->link();
        $crawler = $client->click($link);

        $buttonNode = $crawler->selectButton('Continue');
        $form = $buttonNode->form();

        $client->submit($form, [
            'remote_desktop[title]' => 'My first remote desktop',
            'remote_desktop[kind]' => '0' // "Gaming"
        ]);

        $crawler = $client->followRedirect();

        // Verify that we went into the instance creation workflow
        $container = $client->getContainer();
        $em = $container->get('doctrine.orm.entity_manager');
        /** @var EntityRepository $repo */
        $repo = $em->getRepository('AppBundle\Entity\RemoteDesktop\RemoteDesktop');
        /** @var RemoteDesktop $remoteDesktop */
        $remoteDesktop = $repo->findOneBy(['title' => 'My first remote desktop']);
        $this->assertEquals(
            '/en/remoteDesktops/' . $remoteDesktop->getId() . '/cloudInstances/new',
            $client->getRequest()->getRequestUri()
        );

        // We do not follow through, thus not launching an instance, going back to the overview
        $link = $crawler->selectLink('Launch later')->first()->link();
        $crawler = $client->click($link);

        $this->assertEquals(
            0,
            $crawler->filter('div.alert-info:contains("You do not yet have any remote desktops.")')->count()
        );

        $this->assertContains('My first remote desktop', $crawler->filter('h2')->first()->text());

        $this->assertContains('For playing computer games', $crawler->filter('div.remotedesktop-infobox')->first()->text());

        $this->assertContains('Current status:', $crawler->filter('h3')->first()->text());
        $this->assertContains('Not running', $crawler->filter('span.label-default')->first()->text());

        $this->assertContains('Launch this remote desktop', $crawler->filter('.panel-footer a.btn')->first()->text());

        $this->assertEquals(
            1,
            $crawler->filter('.panel-footer a.btn')->count()
        );

        // We want to build on this in other tests
        return $client;
    }

}
