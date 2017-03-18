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
        $client->request('GET', '/de/logout');

        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $this->assertEquals('http://localhost/de/logout-successful', $client->getResponse()->headers->get('location'));

        $crawler = $client->followRedirect();

        $this->assertContains('Sie haben sich erfolgreich ausgeloggt.', $crawler->filter('div.alert-success')->text());
    }

}
