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

}
