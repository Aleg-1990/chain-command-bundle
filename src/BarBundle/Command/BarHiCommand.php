<?php

namespace BarBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BarHiCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('bar:hi')
            ->setDescription('Produce "Hi from Bar!"')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $message = 'Hi from Bar!';
        $this->getContainer()->get('logger')->info($message);
        $output->writeln(sprintf('<info>%s</info>', $message));
    }

}
