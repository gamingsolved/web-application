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

        $this->assertContains('My first remote desktop', $crawler->filter('h2')->text());

        $this->assertContains('For playing computer games', $crawler->filter('span.label-info')->text());

        // Two checks due to line break
        $this->assertContains('Current status:', $crawler->filter('span.label-default')->text());
        $this->assertContains('not running', $crawler->filter('span.label-default')->text());
    }

}
