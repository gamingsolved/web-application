<?php

namespace Tests\Helpers;

use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;

trait Helpers
{
    protected function resetDatabase(KernelInterface $kernel = null)
    {
        if (is_null($kernel)) {
            $client = static::createClient();
            $kernel = $client->getKernel();
        }
        $kernel->boot();

        $application = new Application($kernel);
        $application->setAutoExit(false);

        $input = new ArgvInput(['', 'doctrine:database:drop', '--no-interaction', '--force', '-q']);
        $application->run($input);

        $input = new ArgvInput(['', 'doctrine:database:create', '--no-interaction', '--force', '-q']);
        $application->run($input);

        $output = new ConsoleOutput();
        $input = new ArgvInput(['', 'doctrine:migrations:migrate', '--no-interaction', '-q']);
        $application->run($input, $output);
    }

    protected function getClientThatRegisteredAndActivatedAUser() : Client
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/register/');

        $buttonNode = $crawler->selectButton('Register');

        $form = $buttonNode->form();

        $client->submit($form, array(
            'fos_user_registration_form[email]' => 'testuser@example.com',
            'fos_user_registration_form[username]' => 'testuser@localhost',
            'fos_user_registration_form[plainPassword][first]' => 'test123',
            'fos_user_registration_form[plainPassword][second]' => 'test123',
        ));

        $container = $client->getContainer();
        $um = $container->get('fos_user.user_manager');

        $user = $um->findUserByEmail('testuser@example.com');
        $client->request('GET', '/register/confirm/' . $user->getConfirmationToken());

        return $client;
    }

}
