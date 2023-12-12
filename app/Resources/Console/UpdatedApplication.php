<?php
// File: app\Resources\Console\TestApplication.php
namespace App\Resources\Console;

use App\Console\Commands\Mohammad;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LazyCommand;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Exception\NamespaceNotFoundException;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdatedApplication extends Application
{
    const NAME = 'Alternative Application';
    const VERSION = '1.0';

    // these are normally private, but must now be protected as they exist in doRun()
    protected $command;
    protected $defaultCommand;
    protected $dispatcher;
    protected $runningCommand;

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        parent::__construct(static::NAME, static::VERSION);

        // manually add the commands you want to be handled by this
      //  $this->add(new Mohammad());
    }



    /**
     * {@inheritdoc}
     */
    public function doRun(InputInterface $input, OutputInterface $output)
    {

        if (true === $input->hasParameterOption(['--version', '-F'], true)) {
            $output->writeln($this->getLongVersion());
            return 0;
        }

        try {
            $input->bind($this->getDefinition());
        } catch (ExceptionInterface $e) {
        }

        $name = $this->getCommandName($input);

        if (true === $input->hasParameterOption(['--help', '-H'], true)) {

            if (!$name) {
                $name = 'help';
                $input = new ArrayInput(['command_name' => $this->defaultCommand]);

            } else {
                $this->wantHelps = true;
            }
        }

        if (!$name) {
            $name = $this->defaultCommand;
            $definition = $this->getDefinition();
            $definition->setArguments(array_merge(
                $definition->getArguments(),
                [
                    'command' => new InputArgument('command', InputArgument::OPTIONAL, $definition->getArgument('command')->getDescription(), $name),
                ]
            ));
        }

        try {
            $this->runningCommand = null;
            // the command name MUST be the first element of the input
            $command = $this->find($name);
        } catch (\Throwable $e) {
            if (!($e instanceof CommandNotFoundException && !$e instanceof NamespaceNotFoundException) || 1 !== \count($alternatives = $e->getAlternatives()) || !$input->isInteractive()) {
                if (null !== $this->dispatcher) {
                    $event = new ConsoleErrorEvent($input, $output, $e);
                    $this->dispatcher->dispatch($event, ConsoleEvents::ERROR);

                    if (0 === $event->getExitCode()) {
                        return 0;
                    }

                    $e = $event->getError();
                }

                throw $e;
            }

            $alternative = $alternatives[0];

            $style = new SymfonyStyle($input, $output);
            $output->writeln('');
            $formattedBlock = (new FormatterHelper())->formatBlock(sprintf('Command "%s" is not defined.', $name), 'error', true);
            $output->writeln($formattedBlock);
            if (!$style->confirm(sprintf('Do you want to run "%s" instead? ', $alternative), false)) {
                if (null !== $this->dispatcher) {
                    $event = new ConsoleErrorEvent($input, $output, $e);
                    $this->dispatcher->dispatch($event, ConsoleEvents::ERROR);

                    return $event->getExitCode();
                }

                return 1;
            }

            $command = $this->find($alternative);
        }

        if ($command instanceof LazyCommand) {
            $command = $command->getCommand();
        }

        $this->runningCommand = $command;
        $exitCode = $this->doRunCommand($command, $input, $output);
        $this->runningCommand = null;

        return $exitCode;
    }

    /**
     * {@inheritdoc}
     *
     * Return everything from the default input definition except the 'version' option
     */
    protected function getDefaultInputDefinition(): InputDefinition
    {
        return new InputDefinition([
            new InputArgument('command', InputArgument::REQUIRED, 'The command to execute'),
//            new InputOption('--help', '-H', InputOption::VALUE_NONE, 'Display help for the given command. When no command is given display help for the <info>'.$this->defaultCommand.'</info> command'),
        ]);

    }
   

}
