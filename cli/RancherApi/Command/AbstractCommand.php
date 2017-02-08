<?php

namespace RancherApi\Command;

use RancherApi\Client\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AbstractCommand extends Command
{
    /**
     * @var Client
     */
    protected $client;

    /**
     *
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $endpoint = $input->getArgument('endpoint');
        $accessKey = $input->getOption('access-key');
        $secretKey = $input->getOption('secret-key');
        $this->client = new Client($endpoint, $accessKey, $secretKey);
    }
}
