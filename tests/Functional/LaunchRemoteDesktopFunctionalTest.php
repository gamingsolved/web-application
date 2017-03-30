<?php

namespace Tests\Functional;

use AppBundle\Entity\Billing\AccountMovement;
use AppBundle\Entity\Billing\AccountMovementRepository;
use AppBundle\Entity\CloudInstance\CloudInstance;
use AppBundle\Entity\RemoteDesktop\Event\RemoteDesktopEvent;
use AppBundle\Entity\RemoteDesktop\RemoteDesktop;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\DomCrawler\Crawler;
use Tests\Helpers\Helpers;

class LaunchRemoteDesktopFunctionalTest extends WebTestCase
{
    use Helpers;

    protected function verifyDektopStatusBooting(Client $client, Crawler $crawler)
    {
        $link = $crawler->selectLink('Refresh status')->first()->link();
        $crawler = $client->click($link);

        $this->assertContains('My first remote desktop', $crawler->filter('h2')->first()->text());

        $this->assertContains('Costs per hour', $crawler->filter('div.hourlycostsbox')->first()->text());
        $this->assertContains('(only in status Ready to use): $1.99', $crawler->filter('div.hourlycostsbox')->first()->text());

        $this->assertContains('Current status:', $crawler->filter('h3')->first()->text());

        $this->assertContains('Booting...', $crawler->filter('span.label')->first()->text());

        $this->assertContains('Refresh status', $crawler->filter('.panel-footer a.btn')->first()->text());
        $this->assertEquals(
            1,
            $crawler->filter('.panel-footer a.btn')->count()
        );
    }

    public function testLaunchRemoteDesktop()
    {
        $client = (new CreateRemoteDesktopFunctionalTest())->testCreateRemoteDesktop();

        $crawler = $client->request('GET', '/en/remoteDesktops/');

        $link = $crawler->selectLink('Launch this remote desktop')->first()->link();

        $crawler = $client->click($link);

        $container = $client->getContainer();
        /** @var EntityManager $em */
        $em = $container->get('doctrine.orm.entity_manager');
        $remoteDesktopRepo = $em->getRepository(RemoteDesktop::class);
        /** @var RemoteDesktop $remoteDesktop */
        $remoteDesktop = $remoteDesktopRepo->findOneBy(['title' => 'My first remote desktop']);
        $this->assertEquals(
            '/en/remoteDesktops/' . $remoteDesktop->getId() . '/cloudInstances/new',
            $client->getRequest()->getRequestUri()
        );

        $this->assertContains('Launch your remote desktop', $crawler->filter('h1')->first()->text());

        $buttonNode = $crawler->selectButton('Launch now');
        $form = $buttonNode->form();

        $client->submit($form, [
            'form[region]' => 'eu-central-1',
        ]);

        $crawler = $client->followRedirect();

        // We want to be back in the overview
        $this->assertEquals(
            '/en/remoteDesktops/',
            $client->getRequest()->getRequestUri()
        );


        // At this point, the instance is in "Scheduled for launch" state

        $this->verifyDektopStatusBooting($client, $crawler);


        // Switching instance to "Launching" status, which must not change the desktop status

        $remoteDesktop = $remoteDesktopRepo->findOneBy(['title' => 'My first remote desktop']);
        /** @var CloudInstance $cloudInstance */
        $cloudInstance = $remoteDesktop->getCloudInstances()->get(0);
        $cloudInstance->setRunstatus(CloudInstance::RUNSTATUS_LAUNCHING);
        $em->persist($cloudInstance);
        $em->flush();

        $this->verifyDektopStatusBooting($client, $crawler);


        // Switching instance to "Running" status, which must put the desktop into "Ready to use" status

        $remoteDesktop = $remoteDesktopRepo->findOneBy(['title' => 'My first remote desktop']);
        /** @var CloudInstance $cloudInstance */
        $cloudInstance = $remoteDesktop->getCloudInstances()->get(0);
        $cloudInstance->setPublicAddress('121.122.123.124');
        $cloudInstance->setAdminPassword('foo');
        $cloudInstance->setRunstatus(CloudInstance::RUNSTATUS_RUNNING);
        $em->persist($cloudInstance);
        $em->flush();

        $remoteDesktopEventRepo = $em->getRepository(RemoteDesktopEvent::class);
        $remoteDesktopEvents = $remoteDesktopEventRepo->findAll();
        $this->assertEquals(
            1,
            sizeof($remoteDesktopEvents)
        );
        /** @var RemoteDesktopEvent $remoteDesktopEvent */
        $remoteDesktopEvent = $remoteDesktopEvents[0];
        $this->assertEquals(
            $remoteDesktopEvent->getEventType(),
            RemoteDesktopEvent::EVENT_TYPE_DESKTOP_FINISHED_LAUNCHING
        );

        $link = $crawler->selectLink('Refresh status')->first()->link();
        $crawler = $client->click($link);

        $this->assertContains('My first remote desktop', $crawler->filter('h2')->first()->text());

        $this->assertContains('Current hourly costs', $crawler->filter('div.hourlycostsbox')->first()->text());
        $this->assertContains('(while in status Ready to use): $1.99', $crawler->filter('div.hourlycostsbox')->first()->text());

        $this->assertContains('Current status:', $crawler->filter('h3')->first()->text());
        $this->assertContains('Ready to use', $crawler->filter('span.label')->first()->text());
        $this->assertContains('Stop this remote desktop', $crawler->filter('a.remotedesktop-action-button')->first()->text());

        $this->assertContains('IP address:', $crawler->filter('li.list-group-item')->eq(0)->text());
        $this->assertContains('121.122.123.124', $crawler->filter('li.list-group-item')->eq(0)->text());

        $this->assertContains('Username:', $crawler->filter('li.list-group-item')->eq(1)->text());
        $this->assertContains('Administrator', $crawler->filter('li.list-group-item')->eq(1)->text());

        $this->assertContains('Password:', $crawler->filter('li.list-group-item')->eq(2)->text());
        $this->assertContains('foo', $crawler->filter('li.list-group-item')->eq(2)->text());

        $this->assertContains('Connect now', $crawler->filter('li.list-group-item')->eq(3)->filter('a.btn')->text());
        $this->assertContains('You need to have the client program installed.', $crawler->filter('li.list-group-item')->eq(3)->filter('span.label')->text());

        $this->assertContains('Download the Windows client', $crawler->filter('li.list-group-item')->eq(4)->filter('a.btn')->eq(0)->text());
        $this->assertContains('Download the Mac client', $crawler->filter('li.list-group-item')->eq(4)->filter('a.btn')->eq(1)->text());


        // Check that the CGX launcher URI is correct and its target SGX file URI works

        $remoteDesktop = $remoteDesktopRepo->findOneBy(['title' => 'My first remote desktop']);

        $launcherUri = $crawler->filter('li.list-group-item')->eq(3)->filter('a.btn')->attr('href');
        $this->assertEquals(
            'sgxportal://'
                . $client->getRequest()->getHttpHost()
                . ':'
                . $client->getRequest()->getPort()
                . '/en/remoteDesktop/'
                . $remoteDesktop->getId()
                . '?protocol='
                . $client->getRequest()->getScheme()
                . '&version=1_8#'
                . $remoteDesktop->getId(),
            $launcherUri
        );

        // /remoteDesktops/{remoteDesktop}/sgx_files/{tag}.sgx
        $client->request('GET', '/en/remoteDesktops/' . $remoteDesktop->getId() . '/sgx_files/' . $remoteDesktop->getId() . '.sgx');

        $this->assertContains('ip: 121.122.123.124', $client->getResponse()->getContent());
        $this->assertContains('key: ' . $remoteDesktop->getId(), $client->getResponse()->getContent());

        // Check that billing worked
        $kernel = static::createClient()->getKernel();
        $kernel->boot();

        $application = new Application($kernel);
        $application->setAutoExit(false);

        $input = new ArgvInput(['', 'app:generatebillableitems', '--no-interaction', '--force', '-q']);
        $application->run($input);

        /** @var AccountMovementRepository $accountMovementRepo */
        $accountMovementRepo = $em->getRepository(AccountMovement::class);

        $this->assertSame(
            98.01,
            $accountMovementRepo->getAccountBalanceForUser($remoteDesktop->getUser())
        );

        // We want to build on this in other tests
        return $client;
    }

    public function testCannotLaunchRemoteDesktopIfBalanceTooLow()
    {
        $client = (new CreateRemoteDesktopFunctionalTest())->testCreateRemoteDesktop();

        $this->resetAccountBalanceForTestuser($client);

        $crawler = $client->request('GET', '/en/remoteDesktops/');

        $link = $crawler->selectLink('Launch this remote desktop')->first()->link();

        $crawler = $client->click($link);

        $this->assertContains('Launch your remote desktop', $crawler->filter('h1')->first()->text());

        $buttonNode = $crawler->selectButton('Launch now');
        $form = $buttonNode->form();

        $crawler = $client->submit($form, [
            'form[region]' => 'eu-central-1',
        ]);

        $this->assertContains(
            'Your account balance is too low to use this remote desktop.',
            $crawler->filter('div.alert')->first()->text()
        );

        $this->assertContains(
            'Running this remote desktop costs $1.99 per hour, but your current balance is',
            $crawler->filter('div.alert')->first()->text()
        );

        $this->assertContains(
            '$0.00.',
            $crawler->filter('div.alert')->first()->text()
        );

        $this->assertContains(
            'Click here to increase your balance now',
            $crawler->filter('a.btn')->first()->text()
        );
    }

}
