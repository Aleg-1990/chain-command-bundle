<?php

namespace OroTest\ChainCommandBundle\Test\DependencyInjection;

use OroTest\ChainCommandBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function shouldSuccessBuildTree()
    {
        $configuration = new Configuration();
        $treeBuilder = $configuration->getConfigTreeBuilder();

        $processor = new Processor();

        $config = $processor->process($treeBuilder->buildTree(), array(
            'oro_test_chain_command' => array(
                'chain' => array(
                    array(
                        'parent'   => 'foo',
                        'children' => array('bar')
                    )
                )
            ),
        ));

        self::assertEquals(array(
            'chain' => array(
                'foo' => array(
                    'children' => array('bar')
                )
            )
        ), $config);
    }
}