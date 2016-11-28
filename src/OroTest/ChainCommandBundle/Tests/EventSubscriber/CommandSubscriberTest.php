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

    /**
     * @test
     */
    public function shouldErrorIfChained()
    {
        $output = new BufferedOutput();
        $event = new ConsoleCommandEvent(new Command('foo'), $this->getMock('Symfony\Component\Console\Input\InputInterface'), $output);
        $subscriber = $this->getSubscriber(array('bar' => array('children' => array('foo'))));
        $subscriber->errorIfChained($event);

        self::assertFalse($event->commandShouldRun());
    }

    /**
     * @test
     */
    public function shouldRunIfNotChained()
    {
        $output = new BufferedOutput();
        $event = new ConsoleCommandEvent(new Command('foo'), $this->getMock('Symfony\Component\Console\Input\InputInterface'), $output);
        $subscriber = $this->getSubscriber(array());
        $subscriber->errorIfChained($event);

        self::assertTrue($event->commandShouldRun());
    }

    /**
     * @test
     */
    public function shouldGetName()
    {
        $commandName = 'foo';
        $output = new BufferedOutput();
        $event = new ConsoleCommandEvent(new Command($commandName), $this->getMock('Symfony\Component\Console\Input\InputInterface'), $output);
        $subscriber = $this->getSubscriber(array());

        $reflection = new \ReflectionClass(get_class($subscriber));
        $method = $reflection->getMethod('getCommandName');
        $method->setAccessible(true);

        self::assertEquals($method->invokeArgs($subscriber, array($event)), $commandName);
        self::assertNotEquals($method->invokeArgs($subscriber, array($event)), 'invalid:name');
    }

    /**
     * @test
     */
    public function shouldRunChained()
    {
        $output = new BufferedOutput();
        $application = new Application();
        $application->setHelperSet(new HelperSet());
        $commandFoo = new Command('foo');
        $commandFoo->setCode(function () {});
        $commandBar = new Command('bar');
        $commandBar->setCode(function () {});
        $application->addCommands(array($commandFoo, $commandBar));

        $event = new ConsoleTerminateEvent($commandBar, $this->getMock('Symfony\Component\Console\Input\InputInterface'), $output, 0);
        $subscriber = $this->getSubscriber(array('bar' => array('children' => array('foo'))));

        self::assertEquals($subscriber->runChainedCommands($event), array('foo' => 0));
    }

    /**
     * @test
     */
    public function shouldIgnoreIfCommandNotFound()
    {
        $output = new BufferedOutput();
        $application = new Application();
        $application->setHelperSet(new HelperSet());
        $commandFoo = new Command('foo');
        $commandFoo->setCode(function () {});
        $commandBar = new Command('bar');
        $commandBar->setCode(function () {});
        $application->addCommands(array($commandFoo, $commandBar));

        $event = new ConsoleTerminateEvent($commandFoo, $this->getMock('Symfony\Component\Console\Input\InputInterface'), $output, 0);
        $subscriber = $this->getSubscriber(array('foo' => array('children' => array('bar', 'baz'))));

        self::assertEquals($subscriber->runChainedCommands($event), array('bar' => 0));
    }

    /**
     * @test
     */
    public function shouldFalseIfNoChains()
    {
        $output = new BufferedOutput();
        $application = new Application();
        $application->setHelperSet(new HelperSet());
        $commandFoo = new Command('foo');
        $commandFoo->setCode(function () {});
        $commandBar = new Command('bar');
        $commandBar->setCode(function () {});
        $application->addCommands(array($commandFoo, $commandBar));

        $event = new ConsoleTerminateEvent($commandBar, $this->getMock('Symfony\Component\Console\Input\InputInterface'), $output, 0);
        $subscriber = $this->getSubscriber(array());

        self::assertFalse($subscriber->runChainedCommands($event));
    }

    /**
     * @test
     */
    public function shouldNotWriteLogIfNotParent()
    {
        $commandName = 'foo';
        $output = new BufferedOutput();
        $event = new ConsoleCommandEvent(new Command($commandName), $this->getMock('Symfony\Component\Console\Input\InputInterface'), $output);
        $subscriber = $this->getSubscriber(array());
        self::assertFalse($subscriber->writeLogIfParent($event));
    }

    /**
     * @test
     */
    public function shouldWriteLogIfParent()
    {
        $commandName = 'foo';
        $output = new BufferedOutput();
        $application = new Application();
        $application->setHelperSet(new HelperSet());
        $command = new Command($commandName);
        $application->addCommands(array($command));
        $event = new ConsoleCommandEvent($command, $this->getMock('Symfony\Component\Console\Input\InputInterface'), $output);
        $subscriber = $this->getSubscriber(array('foo' => array('children' => array('bar'))));
        self::assertTrue($subscriber->writeLogIfParent($event));
    }

    /**
     * @param array $config
     *
     * @return CommandSubscriber
     */
    private function getSubscriber(array $config)
    {
        $logger = new NullLogger();
        return new CommandSubscriber($logger, $config);
    }
}
