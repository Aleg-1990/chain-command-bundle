<?php

namespace OroTest\ChainCommandBundle\EventSubscriber;

use Symfony\Component\Console\Event\ConsoleTerminateEvent;
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
     */
    public function runChainedCommands(ConsoleTerminateEvent $event)
    {

    }

    /**
     * @param ConsoleCommandEvent $event
     */
    public function errorIfChained(ConsoleCommandEvent $event)
    {

    }
}