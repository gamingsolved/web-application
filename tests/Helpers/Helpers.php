<?php

namespace Tests\Helpers;

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

}
