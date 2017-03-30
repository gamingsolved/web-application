<?php

namespace Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Tests\Helpers\Helpers;

class LogoutFunctionalTest extends WebTestCase
{
    use Helpers;


    public function testCorrectLocaleHandlingForLogout()
    {
        $this->resetDatabase();

        $client = $this->getClientThatRegisteredAndActivatedAUser();
        $client->request('GET', '/en/logout');

        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $this->assertEquals('http://localhost/en/logout-successful', $client->getResponse()->headers->get('location'));

        $crawler = $client->followRedirect();

        $this->assertContains('You are now logged out.', $crawler->filter('div.alert-success')->text());
    }

}
