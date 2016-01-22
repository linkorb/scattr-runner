<?php

namespace Scattr\Runner\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Scattr\Client\Client;

class JobCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('job')
            ->setDescription('execute job')
            ->addArgument(
                'config_file',
                InputArgument::REQUIRED,
                'jobs.json config file'
            )
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $verbose = false;
        
        if ($input->getOption('verbose')) {
            $verbose = true;
        }
        $runnerConfigPath = $input->getArgument('config_file');
        if (!file_exists($runnerConfigPath))
        {
            $output->writeln("Config file does not exist\n");
            return;
        }

        $runnerConfig = json_decode(file_get_contents($runnerConfigPath), true);
        if (!$runnerConfig)
        {
            $output->writeln("No data or wrong format in config file\n");
            return;
        }

        if (!isset($runnerConfig['sleepSeconds']) || !isset($runnerConfig['commands']))
        {
            $output->writeln("sleepSeconds or commands not set\n");
            return;
        }

        $commands = [];
        foreach ($runnerConfig['commands'] as $c)
        {
            $commands[$c['command']] = $c;
        }

        $username = getenv('SCATTR_USERNAME');
        $password = getenv('SCATTR_PASSWORD');
        $accountName = getenv('SCATTR_ACCOUNTNAME');
        $poolName = getenv('SCATTR_POOLNAME');
        $url = getenv('SCATTR_URL');
        
        if ($verbose) {
            $output->writeln("Starting scattr-runner");
            $output->writeln(" - url: " . $url);
            $output->writeln(" - username: " . $username);
            $output->writeln(" - pool: " . $accountName . '/' . $poolName);
        }

        if (!$username || !$password || !$accountName || !$url || !$poolName) {
            $output->writeln("Not all required env variables are set (refer to README.md for details)\n");
            return;
        }

        $client = new Client($username, $password, $accountName, $poolName, $url);

        while (true) {
            $job = $client->popJob();
            if (!$job) {
                if ($verbose) {
                    $output->writeln("No jobs");
                }
                sleep($runnerConfig['sleepSeconds']);
                continue;
            }
            

            if ($verbose) {
                $output->writeln("Received Job #" . $job->getId() . ' command [' . $job->getCommand() . ']');
                foreach ($job->getParameters() as $k => $v) {
                    $output->writeln(' - ' . $k . ' = ' . $v);
                }
            }

            if (!array_key_exists($job->getCommand(), $commands)) {
                if ($verbose) {
                    $output->writeln("ERROR: Unconfigured command");
                }
                $client->setFinished($job, 'FAILURE');
                $client->postJobLog($job, 'error', "Command {$job->getCommand()} does not exist in commands in config file");
            } else {
                $res = [];
                foreach ($job->getParameters() as $k => $v) {
                    $res['{{'.$k.'}}'] = $v;
                }
                // remove possible white spaces in {{ variable}}
                $command = preg_replace('/{{(\s*)(.*?)(\s*)}}/', '{{$2}}', $commands[$job->getCommand()]['template']);
                // replace variables with parameters
                $command = str_replace(array_keys($res), array_values($res), $command);

                passthru($command . ' 2>error 1>output');
                $error = file_get_contents('error');
                if ($error) {
                    $client->setFinished($job, 'FAILURE');
                    $client->postJobLog($job, 'error', $error);
                    if ($verbose) {
                        $output->writeln("Error: " . $error);
                    }
                } else {
                    $client->setFinished($job, 'SUCCESS');
                    $out = file_get_contents('output');
                    if ($out) {
                        $client->postJobLog($job, 'info', $out);
                        if ($verbose) {
                            $output->writeln("Info: " . $out);
                        }
                    }
                }
            }
            sleep($runnerConfig['sleepSeconds']);
        }
    }
}
