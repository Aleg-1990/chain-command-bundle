<?php

namespace OroTest\ChainCommandBundle\Test\EventSubscriber;

use OroTest\ChainCommandBundle\EventSubscriber\CommandSubscriber;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Output\BufferedOutput;

class CommandSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function shouldErrorIfChained()
    {
        $output = new BufferedOutput();
        $logger = $this->getMock(NullLogger::class);
        $event = new ConsoleCommandEvent(new Command('foo'), $this->getMock('Symfony\Component\Console\Input\InputInterface'), $output);
        $listener = new CommandSubscriber($logger, array('bar' => array('children' => array('foo'))));
        $listener->errorIfChained($event);

        self::assertFalse($event->commandShouldRun());
    }

    /**
     * @test
     */
    public function shouldRunIfNotChained()
    {
        $output = new BufferedOutput();
        $logger = $this->getMock(NullLogger::class);
        $event = new ConsoleCommandEvent(new Command('foo'), $this->getMock('Symfony\Component\Console\Input\InputInterface'), $output);
        $listener = new CommandSubscriber($logger, array());
        $listener->errorIfChained($event);

        self::assertTrue($event->commandShouldRun());
    }

    /**
     * @test
     */
    public function shouldGetName()
    {
        $commandName = 'foo';
        $output = new BufferedOutput();
        $logger = $this->getMock(NullLogger::class);
        $event = new ConsoleCommandEvent(new Command($commandName), $this->getMock('Symfony\Component\Console\Input\InputInterface'), $output);
        $listener = new CommandSubscriber($logger, array());

        $reflection = new \ReflectionClass(get_class($listener));
        $method = $reflection->getMethod('getCommandName');
        $method->setAccessible(true);

        self::assertEquals($method->invokeArgs($listener, array($event)), $commandName);
        self::assertNotEquals($method->invokeArgs($listener, array($event)), 'invalid:name');
    }

    /**
     * @test
     */
    public function shouldRunChained()
    {
        $output = new BufferedOutput();
        $logger = $this->getMock(NullLogger::class);
        $application = new Application();
        $application->setHelperSet(new HelperSet());
        $commandFoo = (new Command('foo'))->setCode(function () {});
        $commandBar = (new Command('bar'))->setCode(function () {});
        $application->addCommands(array($commandFoo, $commandBar));

        $event = new ConsoleTerminateEvent($commandBar, $this->getMock('Symfony\Component\Console\Input\InputInterface'), $output, 0);
        $listener = new CommandSubscriber($logger, array('bar' => array('children' => array('foo'))));

        self::assertEquals($listener->runChainedCommands($event), array('foo' => 0));
    }

    /**
     * @test
     */
    public function shouldFalseIfNoChains()
    {
        $output = new BufferedOutput();
        $logger = $this->getMock(NullLogger::class);
        $application = new Application();
        $application->setHelperSet(new HelperSet());
        $commandFoo = (new Command('foo'))->setCode(function () {});
        $commandBar = (new Command('bar'))->setCode(function () {});
        $application->addCommands(array($commandFoo, $commandBar));

        $event = new ConsoleTerminateEvent($commandBar, $this->getMock('Symfony\Component\Console\Input\InputInterface'), $output, 0);
        $listener = new CommandSubscriber($logger, array());

        self::assertFalse($listener->runChainedCommands($event));
    }

    /**
     * @test
     */
    public function shouldReturnEvents()
    {
        self::assertEquals(CommandSubscriber::getSubscribedEvents(), array(
            ConsoleEvents::COMMAND => array(
                array('errorIfChained', 1),
                array('writeLogIfParent', 2),
            ),
            ConsoleEvents::TERMINATE => 'runChainedCommands',
        ));
    }
}
