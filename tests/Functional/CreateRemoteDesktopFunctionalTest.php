<?php

namespace Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Tests\Helpers\Helpers;

class CreateRemoteDesktopFunctionalTest extends WebTestCase
{
    use Helpers;

    public function testRemoteDesktopsListNotAvailableWhenNotLoggedIn()
    {
        $this->resetDatabase();

        $client = static::createClient();

        $client->request('GET', '/remoteDesktops/');

        $this->assertEquals(302, $client->getResponse()->getStatusCode());

        $this->assertEquals('http://localhost/login', $client->getResponse()->headers->get('location'));
    }

    public function testRemoteDesktopsListAvailableWhenLoggedIn()
    {
        $this->resetDatabase();

        $client = $this->getClientThatRegisteredAndActivatedAUser();

        $crawler = $client->request('GET', '/remoteDesktops/');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertContains('Your remote desktops', $crawler->filter('h1')->text());

        $this->assertContains('You do not yet have any remote desktops.', $crawler->filter('div.alert-info')->text());
    }

}
