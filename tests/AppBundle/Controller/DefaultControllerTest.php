<?php

namespace Tests\AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DefaultControllerTest extends WebTestCase
{
    public function testRoot()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/');

        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $this->assertEquals('/en/', $client->getResponse()->headers->get('Location'));
    }

    public function testRootEn()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/en/');

        $this->assertEquals(302, $client->getResponse()->getStatusCode());
        $this->assertEquals('/en/mac', $client->getResponse()->headers->get('Location'));
    }

    public function testMacHomepage()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/en/mac');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertContains('Gaming on Mac is finally solved.', $crawler->filter('body')->text());
    }
}
