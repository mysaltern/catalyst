<?php
// File: app\Resources\Console\TestApplication.php

namespace App\Resources\Console;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputDefinition;

class TestApplication extends Application
{
    public const NAME = 'Alternative Application';
    public const VERSION = '2.0';

    public function __construct()
    {
        parent::__construct(static::NAME, static::VERSION);

        $this->add(new class() extends Command {
            protected static $defaultName = 'user:upload';

            protected function configure()
            {
                $this
                    ->setName('user:upload')
                    ->addOption('h', '-h', InputOption::VALUE_REQUIRED, 'Host')
                    ->addOption('custom-help', null, InputOption::VALUE_NONE, 'Custom help');
            }

            protected function execute(InputInterface $input, OutputInterface $output)
            {
                if ($input->getOption('custom-help')) {
                    $output->writeln('Custom help requested');
                    // Handle the custom help option
                }

                if ($input->getOption('h')) {
                    $output->writeln('Host option provided');
                    // Handle the 'h' option
                }

                return Command::SUCCESS;
            }
        });
    }

    protected function getDefaultInputDefinition(): InputDefinition
    {
        $inputDefinition = parent::getDefaultInputDefinition();

        // Get the '--help' option index
        $helpOptionIndex = null;
        foreach ($inputDefinition->getOptions() as $index => $option) {
            if ($option->getName() === 'help') {
                $helpOptionIndex = $index;
                break;
            }
        }

        // If '--help' option found, remove it and add a new one with the modified shortcut
        if ($helpOptionIndex !== null) {
            $options = $inputDefinition->getOptions();
            unset($options[$helpOptionIndex]);
            $inputDefinition = new InputDefinition(array_values($options));
        }

        // Add the modified '--help' option with the new shortcut '-hh'
        $inputDefinition->addOption(new InputOption('--help', '-hh', InputOption::VALUE_NONE, 'Display help for the given command. When no command is given display help for the default command'));

        return $inputDefinition;
    }
}
