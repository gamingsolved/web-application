<?php

namespace Tests\Functional;

use AppBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Tests\Helpers\Helpers;

class AdministrationFunctionalTest extends WebTestCase
{
    use Helpers;

    public function testNonAdminUserCannotReachAdministrationArea()
    {
        $this->resetDatabase();

        $client = $this->getClientThatRegisteredAndActivatedAUser();
        $client->request('GET', '/en/administration/');

        $this->assertEquals(403, $client->getResponse()->getStatusCode());
    }

    public function testAdminUserCanReachAdministrationArea()
    {
        $this->resetDatabase();

        $client = $this->getClientThatRegisteredAndActivatedAUser();
        $client->followRedirects();

        $container = $client->getContainer();
        $um = $container->get('fos_user.user_manager');

        /** @var EntityManager $em */
        $em = $container->get('doctrine.orm.entity_manager');

        /** @var User $user */
        $user = $um->findUserByEmail('testuser@example.com');

        $user->addRole('ROLE_ADMIN');

        $em->persist($user);
        $em->flush();

        // We need a new session after role change
        $client->request('GET', '/logout');

        $crawler = $client->request('GET', '/login');

        $buttonNode = $crawler->selectButton('Log in');

        $form = $buttonNode->form();

        $client->submit($form, array(
            '_username' => 'testuser@example.com',
            '_password' => 'test123'
        ));

        $crawler = $client->request('GET', '/en/administration/');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertContains(
            'Administration home',
            $crawler->filter('h1')->first()->text()
        );
    }

}
