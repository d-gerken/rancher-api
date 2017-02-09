<?php

namespace RancherApi\Command;

use GuzzleHttp\Client;
use RancherApi\Exception\OptionMissinException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExecuteCommand extends AbstractCommand
{
    /**
     *
     */
    protected function configure()
    {
        $this
            ->setName('rancher:exec')
            ->setDescription('Exec commands inside containers via Rancher-API')
            ->setDefinition(
                new InputDefinition([
                    new InputOption('environment', 'env', InputOption::VALUE_OPTIONAL),
                    new InputOption('stack', 'st', InputOption::VALUE_REQUIRED),
                    new InputOption('service', 'sv', InputOption::VALUE_REQUIRED),
                    new InputOption('instance', 'i', InputOption::VALUE_OPTIONAL),
                    new InputOption('cmd', 'c', InputOption::VALUE_REQUIRED)
                ])
            );
    }

    /**
     *
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        /* Get Environment */
        $env = $input->getOption('environment');
        if (empty($env)) {
            $env = 'Default';
        }

        /* Get Stack */
        $stack = $input->getOption('stack');
        if (empty($stack)) {
            throw new OptionMissinException('Missin option "stack"');
        }

        /* Get Service */
        $service = $input->getOption('service');
        if (empty($service)) {
            throw new OptionMissinException('Missing option "service"');
        }

        /* Get Instance */
        $instance = $input->getOption('instance');

        /* Get Command */
        $cmd = $input->getOption('cmd');
        if (empty($cmd)) {
            throw new OptionMissinException('Missing option "cmd"');
        }

        $output->writeln([
            'Starting to execute command with following params...',
            'Environment: ' . $env,
            'Stack: ' . $stack,
            'Service: ' . $service,
            'Command: ' . $cmd
        ]);

        /* Fetch environment Id */
        $envId = $this->fetchByName('/projects', $env);
        if (!$envId) {
            $output->writeln('Environment could not be found');
            return;
        }
        $output->writeln('Environment "' . $env . '" has ID: ' . $envId);

        /* Fetch stack Id */
        $stackId = $this->fetchByName('/projects/' . $envId . '/stacks', $stack);
        if (!$stackId) {
            $output->writeln('Stack could not be found');
            return;
        }
        $output->writeln('Stack "' . $stack . '" has ID: ' . $stackId);

        /* Fetch service Id */
        $serviceId = $this->fetchByName('/projects/' . $envId . '/stacks/' . $stackId . '/services', $service);
        if (!$serviceId) {
            $output->writeln('Service could not be found');
            return;
        }
        $output->writeln('Service "' . $service . '" has ID: ' . $serviceId);

        /* Get instances */
        $instances = $this->client->get('/projects/' . $envId . '/services/' . $serviceId . '/instances/');
        if (null === $instance) {
            /* Get first instance in service */
            $instanceId = $instances['data'][0]['id'];
        } else {
            /* Look for instance with defined name */
            foreach ($instances['data'] as $instanceObj) {
                /* Check name */
                $nameSplit = explode('-', $instanceObj['name']);
                $instanceName = $nameSplit[count($nameSplit) - 2];
                if ($instanceName == $instance) {
                    $instanceId = $instanceObj['id'];
                    break;
                }
            }
        }
        if (null === $instanceId) {
            $output->writeln('No instance found');
            return;
        }
        $output->writeln('Instance-ID: ' . $instanceId);

        /* Send command to instance */
        $response = $this->client->post('/projects/' . $envId . '/containers/' . $instanceId . '?action=execute', [
            'attachStdin' => true,
            'attachStdout' => true,
            'command' => [
                'ls -al'
            ],
            'tty' => true
        ]);

        /* Grab token and url to run websocket command */
        $this->client->runWebsocket($response['url'], $response['token']);
    }

    /**
     * @param string $path
     * @param string $name
     * @return false|string
     */
    protected function fetchByName($path, $name)
    {
        try {
            $data = $this->client->get($path);
        } catch (\Exception $e) {
            return false;
        }

        if (!is_array($data)) {
            return false;
        }

        $id = null;
        foreach ($data['data'] as $item) {
            if ($item['name'] == $name) {
                $id = $item['id'];
            }
        }
        if (null === $id) {
            return false;
        }
        return $id;
    }
}
