<?php

namespace OroTest\ChainCommandBundle\EventSubscriber;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Event\ConsoleEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;

/**
 * Subscribe for console command events and run chained commands.
 */
class CommandSubscriber implements EventSubscriberInterface
{
    /**
     * @var array
     */
    private $chain;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * CommandSubscriber constructor.
     *
     * @param LoggerInterface $logger
     * @param array           $chain  This argument is added in extension class.
     */
    public function __construct(LoggerInterface $logger, array $chain)
    {
        $this->chain = $chain;
        $this->logger = $logger;
    }
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            ConsoleEvents::COMMAND => array(
                array('errorIfChained', 1),
                array('writeLogIfParent', 2),
            ),
            ConsoleEvents::TERMINATE => 'runChainedCommands',
        );
    }

    /**
     * @param ConsoleTerminateEvent $event
     *
     * @return array|bool
     */
    public function runChainedCommands(ConsoleTerminateEvent $event)
    {
        $commandName = $this->getCommandName($event);
        $application = $event->getCommand()->getApplication();
        if (isset($this->chain[$commandName]) && count($this->chain[$commandName]) > 0) {
            $statusCodes = array();
            $this->logger->info(sprintf('Executing %s chain members:', $commandName));
            foreach ($this->chain[$commandName]['children'] as $chainedCommandName) {
                try {
                    $chainedCommand = $application->find($chainedCommandName);
                    $statusCodes[$chainedCommandName] = $chainedCommand->run(new ArrayInput(array()), $event->getOutput());
                } catch (CommandNotFoundException $e) {
                    $this->logger->error(sprintf('Command "%s" is not found.', $chainedCommandName));
                    $event->getOutput()->writeln(sprintf('<error>Command "%s" is not found.</error>', $chainedCommandName));
                }
            }
            $this->logger->info(sprintf('Execution of %s chain completed.', $commandName));

            return $statusCodes;
        }

        return false;
    }

    /**
     * @param ConsoleCommandEvent $event
     */
    public function errorIfChained(ConsoleCommandEvent $event)
    {
        $commandName = $this->getCommandName($event);
        foreach ($this->chain as $parent => $chain) {
            if (in_array($commandName, $chain['children'], true)) {
                $event->disableCommand();
                $event->getOutput()->writeln(sprintf('Command is declared as chained with parent "%s"', $parent));
            }
        }
    }

    /**
     * @param ConsoleCommandEvent $event
     */
    public function writeLogIfParent(ConsoleCommandEvent $event)
    {
        $commandName = $this->getCommandName($event);
        $application = $event->getCommand()->getApplication();
        if (isset($this->chain[$commandName]) && count($this->chain[$commandName]) > 0) {
            $this->logger->info(sprintf('%s is a master command of a command chain that has registered member commands', $commandName));
            foreach ($this->chain[$commandName]['children'] as $chainedCommandName) {
                try {
                    $application->find($chainedCommandName);
                } catch (CommandNotFoundException $e) {
                    $this->logger->error(sprintf('Command "%s" is not found.', $chainedCommandName));
                    $event->getOutput()->writeln(sprintf('<error>Command "%s" is not found.</error>', $chainedCommandName));
                }
                $this->logger->info(sprintf('%s registered as a member of %s command chain', $chainedCommandName, $commandName));
            }
            $this->logger->info(sprintf('Executing %s command itself first:', $commandName));
        }
    }

    /**
     * Simplify getting command name.
     *
     * @param ConsoleEvent $event
     *
     * @return string Command name.
     */
    private function getCommandName(ConsoleEvent $event)
    {
        return $event->getCommand()->getName();
    }
}
