<?php

namespace AppBundle\Command;

use AppBundle\Entity\Billing\AccountMovement;
use AppBundle\Entity\Billing\BillableItem;
use AppBundle\Entity\RemoteDesktop\Event\RemoteDesktopEvent;
use AppBundle\Entity\RemoteDesktop\RemoteDesktop;
use AppBundle\Service\BillingService;
use AppBundle\Utility\DateTimeUtility;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

date_default_timezone_set('UTC');

class GenerateBillableItemsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('app:generatebillableitems')
            ->setDescription('Generates and persists billable items for the usage of remote desktops')
        ;
    }

    protected function persistBillableItems(array $billableItems, RemoteDesktop $remoteDesktop, OutputInterface $output)
    {
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        foreach ($billableItems as $generatedBillableItem) {
            $output->writeln('Generated billable item: Type ' . $generatedBillableItem->getType() . ', beginning: ' . $generatedBillableItem->getTimewindowBegin()->format('Y-m-d H:i:s'));
            $output->writeln('Trying to persist...');
            $em->persist($generatedBillableItem);
            $em->flush();
            $output->writeln('Done.');
            $output->writeln('Booking a debit for this billable item...');
            $accountMovement = AccountMovement::createDebitMovement($remoteDesktop->getUser(), $generatedBillableItem);
            $em->persist($accountMovement);
            $em->flush();
            $output->writeln('Done.');
        }
        $output->writeln('');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $remoteDesktopRepo = $em->getRepository(RemoteDesktop::class);
        $remoteDesktops = $remoteDesktopRepo->findAll();

        $billingService = new BillingService(
            $em->getRepository(RemoteDesktopEvent::class),
            $em->getRepository(BillableItem::class)
        );

        /** @var RemoteDesktop $remoteDesktop */
        foreach ($remoteDesktops as $remoteDesktop) {
            $output->writeln('Attempting to generate billable items for remote desktop id ' . $remoteDesktop->getId());
            $output->writeln('Desktop owner: ' . $remoteDesktop->getUser()->getUsername());

            /** @var BillableItem[] $generatedUsageBillableItems */
            $generatedUsageBillableItems = $billingService->generateMissingBillableItems(
                $remoteDesktop,
                DateTimeUtility::createDateTime('now'),
                BillableItem::TYPE_USAGE
            );

            $this->persistBillableItems($generatedUsageBillableItems, $remoteDesktop, $output);

            /** @var BillableItem[] $generatedProvisioningBillableItems */
            $generatedProvisioningBillableItems = $billingService->generateMissingBillableItems(
                $remoteDesktop,
                DateTimeUtility::createDateTime('now'),
                BillableItem::TYPE_PROVISIONING
            );

            $this->persistBillableItems($generatedProvisioningBillableItems, $remoteDesktop, $output);

        }

        $output->writeln('All done, exiting.');
    }
}
