<?php

namespace Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Tests\Helpers\Helpers;

class LoginFunctionalTest extends WebTestCase
{
    use Helpers;


    public function testLoginWorksAndLeadsToRemoteDesktopsList()
    {
        $this->resetDatabase();

        $client = $this->getClientThatRegisteredAndActivatedAUser();
        $client->followRedirects();

        $client->request('GET', '/logout');
        $crawler = $client->request('GET', '/login');

        $buttonNode = $crawler->selectButton('Log in');

        $form = $buttonNode->form();

        $crawler = $client->submit($form, array(
            '_username' => 'testuser@example.com',
            '_password' => 'test123'
        ));

        $this->assertEquals('Your cloud gaming rigs', $crawler->filter('h1')->text());
    }

}
