<?php

namespace FooBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FooHelloCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('foo:hello')
            ->setDescription('Produce "Hello from Foo!"')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $message = 'Hello from Foo!';
        $this->getContainer()->get('logger')->info($message);
        $output->writeln(sprintf('<info>%s</info>', $message));
    }

}
