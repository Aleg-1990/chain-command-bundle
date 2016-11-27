<?php

namespace OroTest\ChainCommandBundle\EventSubscriber;

use Symfony\Component\Console\Event\ConsoleEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
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
    private $chain = array();

    /**
     * CommandSubscriber constructor.
     *
     * @param array $config
     */
    public function __construct()
    {
        $args = func_get_args();
        $this->chain = array_pop($args);
    }
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            ConsoleEvents::COMMAND => 'errorIfChained',
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
        if(isset($this->chain[$commandName]) && count($this->chain[$commandName]) > 0) {
            $statusCodes = array();
            foreach ($this->chain[$commandName]['children'] as $chainedCommandName) {
                $chainedCommand = $application->find($chainedCommandName);
                $statusCodes[$chainedCommandName] = $chainedCommand->run(new ArrayInput(array()), $event->getOutput());
            }
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