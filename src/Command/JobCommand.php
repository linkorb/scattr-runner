<?php

namespace Scattr\Runner\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Scattr\Client\Client;

class JobCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('job')
            ->setDescription('execute job')
            ->addArgument('config_file', InputArgument::REQUIRED, 'jobs.json config file');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
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
        $account = getenv('SCATTR_ACCOUNT');
        $poolName = getenv('SCATTR_POOLNAME');
        $url = getenv('SCATTR_URL');
        if (!$username || !$password || !$account || !$url || !$poolName) {
            $output->writeln("Not all required env variables are set\n");
            return;
        }

        $client = new Client($username, $password, $account, $poolName, $url);

        while(true)
        {
            $job = $client->popJob();
            if (!$job)
            {
                continue;
            }

            if (!array_key_exists($job->getCommand(), $commands))
            {
                $client->setFinished($job, 'FAILURE');
                $client->postJobLog($job, 'error', "Command {$job->getCommand()} does not exist in commands in config file");
            }
            else
            {
                $res = [];
                foreach ($job->getParameters() as $k => $v)
                {
                    $res['{{'.$k.'}}'] = $v;
                }
                // remove possible white spaces in {{ variable}}
                $command = preg_replace('/{{(\s*)(.*?)(\s*)}}/', '{{$2}}', $commands[$job->getCommand()]['template']);
                // replace variables with parameters
                $command = str_replace(array_keys($res), array_values($res), $command);

                passthru($command . ' 2>error 1>output');
                $error = file_get_contents('error');
                if ($error)
                {
                    $client->setFinished($job, 'FAILURE');
                    $client->postJobLog($job, 'error', $error);
                }
                else
                {
                    $client->setFinished($job, 'SUCCESS');
                    $out = file_get_contents('output');
                    if ($out)
                    {
                        $client->postJobLog($job, 'info', $out);
                    }
                }
            }
            sleep($runnerConfig['sleepSeconds']);
        }
    }
}
