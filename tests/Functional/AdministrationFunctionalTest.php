<?php

namespace Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Tests\Helpers\Helpers;

class LogoutFunctionalTest extends WebTestCase
{
    use Helpers;

    public function testNonAdminUserCannotReachAdministrationArea()
    {
        $this->resetDatabase();

        $client = $this->getClientThatRegisteredAndActivatedAUser();
        $client->request('GET', '/en/administration/');

        $this->assertEquals(403, $client->getResponse()->getStatusCode());
    }

}
