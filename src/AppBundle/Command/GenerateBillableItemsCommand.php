<?php

namespace AppBundle\Command;

use AppBundle\Entity\Billing\BillableItem;
use AppBundle\Entity\RemoteDesktop\Event\RemoteDesktopEvent;
use AppBundle\Entity\RemoteDesktop\RemoteDesktop;
use AppBundle\Service\BillingService;
use AppBundle\Utility\DateTimeUtility;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateBillableItemsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('app:generatebillableitems')
            ->setDescription('Generates and persists billable items for the usage of remote desktops')
        ;
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

            $generatedBillableItems = $billingService->generateMissingBillableItems($remoteDesktop, DateTimeUtility::createDateTime('now'));

            /** @var BillableItem $generatedBillableItem */
            foreach ($generatedBillableItems as $generatedBillableItem) {
                $output->writeln('Generated billable item: ' . $generatedBillableItem->getTimewindowBegin()->format('Y-m-d H:i:s'));
                $output->writeln('Trying to persist...');
                $em->persist($generatedBillableItem);
                $em->flush();
                $output->writeln('Done.');
            }

            $output->writeln('');
        }

        $output->writeln('All done, exiting.');
    }
}
