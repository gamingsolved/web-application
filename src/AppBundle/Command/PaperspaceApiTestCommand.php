<?php

namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Gamingsolved\Paperspace\Api\Client\Version0_1_3 as PaperspaceApiClient;

date_default_timezone_set('UTC');

class PaperspaceApiTestCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('app:paperspaceapitest')
            ->setDescription('test paperspace api')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $config = PaperspaceApiClient\Configuration::getDefaultConfiguration();
        $config->setApiKey('X-API-Key', '1fb6a2b8a5350cd4c05f5c30866ab2');

        $machinesApiClient = new PaperspaceApiClient\Api\MachinesApi(null, $config);

        /*
        $createMachineParams = new PaperspaceApiClient\Model\CreateMachineParams();
        $createMachineParams->setRegion('East Coast (NY2)');
        $createMachineParams->setMachineType('GPU+');
        $createMachineParams->setSize(50);
        $createMachineParams->setBillingType('hourly');
        $createMachineParams->setMachineName('My first API-launched machine');
        $createMachineParams->setTemplateId('t2q0g8n');
        $createMachineParams->setAssignPublicIp(true);

        try {
            $result = $machinesApiClient->createMachine($createMachineParams);
            print_r($result);
        } catch (\Exception $e) {
            echo 'Exception when calling MachinesApi->createMachine: ', $e->getMessage(), PHP_EOL;
        }
        */

        #$machinesApiClient->startMachine('psg8gwmb');

        try {
            $result = $machinesApiClient->listMachines();
            print_r($result);
        } catch (\Exception $e) {
            echo 'Exception when calling MachinesApi->listMachines: ', $e->getMessage(), PHP_EOL;
        }

        /*
        try {
            $result = $machinesApiClient->restartMachine('psg8gwmb');
            print_r($result);
        } catch (\Exception $e) {
            echo 'Exception when calling MachinesApi->showMachine: ', $e->getMessage(), PHP_EOL;
        }
        */

        /*
        try {
            $result = $machinesApiClient->startMachine('psg8gwmbs');
            print_r($result);
        } catch (\Exception $e) {
            echo 'Exception when calling MachinesApi->startMachine: ', $e->getMessage(), PHP_EOL;
        }
        */


        $templatesApiClient = new PaperspaceApiClient\Api\TemplatesApi(null, $config);

        try {
            $result = $templatesApiClient->listTemplates(null, null, null);
            print_r($result);
        } catch (\Exception $e) {
            echo 'Exception when calling TemplatesApi->listTemplates: ', $e->getMessage(), PHP_EOL;
        }

    }
}
