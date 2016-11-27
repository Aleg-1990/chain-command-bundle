<?php

namespace OroTest\ChainCommandBundle\Test\DependencyInjection;

use OroTest\ChainCommandBundle\DependencyInjection\OroTestChainCommandExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OroTestChainCommandExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function shouldSuccessLoad()
    {
        $configs = array(
            'oro_test_chain_command' => array(
                'chain' => array(
                    array(
                        'parent'   => 'foo',
                        'children' => array('bar')
                    )
                )
            )
        );

        $extension = new OroTestChainCommandExtension();
        $container = new ContainerBuilder();

        $extension->load($configs, $container);

        self::assertEquals($container->getDefinition('oro_test_chain_command.command_subscriber')->getArguments(),
            array(
                array(
                    'foo' => array(
                        'children' => array('bar')
                    )
                )
            )
        );
    }
}