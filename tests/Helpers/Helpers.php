<?php

namespace Tests\Helpers;

use AppBundle\Entity\Billing\AccountMovement;
use AppBundle\Entity\Billing\AccountMovementRepository;
use AppBundle\Entity\Billing\BillableItem;
use AppBundle\Entity\CloudInstance\AwsCloudInstance;
use AppBundle\Entity\RemoteDesktop\Event\RemoteDesktopRelevantForBillingEvent;
use AppBundle\Entity\RemoteDesktop\RemoteDesktop;
use AppBundle\Entity\User;
use AppBundle\Utility\DateTimeUtility;
use Doctrine\ORM\EntityManager;
use JMS\Payment\CoreBundle\Entity\Credit;
use JMS\Payment\CoreBundle\Entity\FinancialTransaction;
use JMS\Payment\CoreBundle\Entity\Payment;
use JMS\Payment\CoreBundle\Entity\PaymentInstruction;
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
            /** @var Client $client */
            $client = static::createClient();
            $kernel = $client->getKernel();
        }
        $kernel->boot();

        $application = new Application($kernel);
        $application->setAutoExit(false);

        $container = $client->getContainer();
        /** @var EntityManager $em */
        $em = $container->get('doctrine.orm.entity_manager');
        $connection = $em->getConnection();
        $dbPlatform = $connection->getDatabasePlatform();

        $entityClasses = [
            AccountMovement::class,
            AwsCloudInstance::class,
            BillableItem::class,
            Credit::class,
            FinancialTransaction::class,
            PaymentInstruction::class,
            Payment::class,
            RemoteDesktopRelevantForBillingEvent::class,
            RemoteDesktop::class,
            User::class
        ];

        foreach ($entityClasses as $entityClass) {
            $cmd = $em->getClassMetadata($entityClass);
            $connection->query('SET FOREIGN_KEY_CHECKS=0;');
            $q = $dbPlatform->getTruncateTableSql($cmd->getTableName());
            $connection->executeUpdate($q);
            $connection->query('SET FOREIGN_KEY_CHECKS=1;');
        }

        $output = new ConsoleOutput();
        $input = new ArgvInput(['', 'doctrine:migrations:migrate', '--no-interaction', '-q']);
        $application->run($input, $output);
    }

    protected function getClientThatRegisteredAndActivatedAUser() : Client
    {
        /** @var Client $client */
        $client = static::createClient();
        $crawler = $client->request('GET', '/en/register/');

        $buttonNode = $crawler->selectButton('Register');

        $form = $buttonNode->form();

        $client->submit($form, array(
            'fos_user_registration_form[email]' => 'testuser@example.com',
            'fos_user_registration_form[username]' => 'testuser',
            'fos_user_registration_form[plainPassword][first]' => 'test123',
            'fos_user_registration_form[plainPassword][second]' => 'test123',
        ));

        $container = $client->getContainer();
        $um = $container->get('fos_user.user_manager');

        /** @var User $user */
        $user = $um->findUserByEmail('testuser@example.com');
        $client->request('GET', '/en/register/confirm/' . $user->getConfirmationToken());

        // We need to give this user a certain balance in order to allow him launching instances
        /** @var EntityManager $em */
        $em = $container->get('doctrine.orm.entity_manager');

        /** @var User $user */
        $user = $um->findUserByEmail('testuser@example.com');

        $accountMovement = AccountMovement::createDepositMovement($user, 100.0);
        $accountMovement->setPaymentFinished(true);
        $em->persist($accountMovement);
        $em->flush();

        return $client;
    }

    protected function resetAccountBalanceForTestuser(Client $client) {
        $container = $client->getContainer();
        $um = $container->get('fos_user.user_manager');

        /** @var EntityManager $em */
        $em = $container->get('doctrine.orm.entity_manager');

        /** @var User $user */
        $user = $um->findUserByEmail('testuser@example.com');

        /** @var AccountMovementRepository $accountMovementRepository */
        $accountMovementRepository = $em->getRepository(AccountMovement::class);
        $accountMovements = $accountMovementRepository->findBy(['user' => $user]);
        foreach ($accountMovements as $accountMovement) {
            $em->remove($accountMovement);
        }
        $em->flush();
    }

}
