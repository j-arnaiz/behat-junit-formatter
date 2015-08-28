<?php

namespace jarnaiz\JUnitFormatter;

use Behat\Testwork\ServiceContainer\Extension as ExtensionInterface;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Class: JUnitFormatterExtension
 *
 * @see ExtensionInterface
 */
class JUnitFormatterExtension implements ExtensionInterface
{
    const ENV_FILENAME = 'BEHAT_JUNIT_FILENAME';
    const ENV_OUTPUTDIR = 'BEHAT_JUNIT_OUTPUTDIR';

    /**
     * process
     *
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
    }

    /**
     * getConfigKey
     *
     * @return string
     */
    public function getConfigKey()
    {
        return 'dizzy7';
    }

    /**
     * initialize
     *
     * @param ExtensionManager $extensionManager
     */
    public function initialize(ExtensionManager $extensionManager)
    {
    }

    /**
     * configure
     *
     * @param ArrayNodeDefinition $builder
     */
    public function configure(ArrayNodeDefinition $builder)
    {
        $builder->children()->scalarNode('filename')->defaultValue('test_report.xml');
        $builder->children()->scalarNode('outputDir')->defaultValue('build/tests');
    }

    /**
     * load
     *
     * @param ContainerBuilder $container
     * @param array            $config
     */
    public function load(ContainerBuilder $container, array $config)
    {

        $definition = new Definition('dizzy7\\JUnitFormatter\\Formatter\\JUnitFormatter');

        if (!$filename = \getenv(self::ENV_FILENAME)) {
            $filename = $config['filename'];
        }

        $definition->addArgument($filename);

        if (!$outputDir = \getenv(self::ENV_OUTPUTDIR)) {
            $outputDir = $config['outputDir'];
        }

        $definition->addArgument($outputDir);

        $container->setDefinition('junit.formatter', $definition)
            ->addTag('output.formatter');
    }
}
