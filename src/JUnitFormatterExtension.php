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
    const ENV_FILENAME = 'JARNAIZ_JUNIT_FILENAME';
    const ENV_OUTPUTDIR = 'JARNAIZ_JUNIT_OUTPUTDIR';
    const ENV_REALTIME = 'JARNAIZ_JUNIT_REALTIME';

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
        return "jarnaizjunit";
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
        $builder->children()->booleanNode('realtime')->defaultValue(false);
    }

    /**
     * load
     *
     * @param ContainerBuilder $container
     * @param array            $config
     */
    public function load(ContainerBuilder $container, array $config)
    {
        if (!$realtime = \getenv(self::ENV_REALTIME)) {
            $realtime = $config['realtime'];
        }

        if ($realtime) {
            $definition = new Definition('jarnaiz\\JUnitFormatter\\Formatter\\JUnitRealtimeFormatter');
        } else {
            $definition = new Definition('jarnaiz\\JUnitFormatter\\Formatter\\JUnitFormatter');
        }

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
