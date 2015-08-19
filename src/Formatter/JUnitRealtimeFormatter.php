<?php

namespace jarnaiz\JUnitFormatter\Formatter;

use Behat\Testwork\Output\Printer\JUnitOutputPrinter;
use Behat\Testwork\Output\Printer\OutputPrinter;
use Behat\Testwork\Tester\Result\TestResult;
use Behat\Behat\EventDispatcher\Event\FeatureTested;
use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Behat\Behat\EventDispatcher\Event\OutlineTested;
use Behat\Behat\EventDispatcher\Event\ExampleTested;
use Behat\Testwork\EventDispatcher\Event\SuiteTested;
use Behat\Testwork\Output\Formatter;
use Behat\Testwork\Counter\Timer;
use jarnaiz\JUnitFormatter\Printer\FileOutputPrinter;

/**
 * Class: JUnitFormatter
 *
 * @see Formatter
 */
class JUnitRealtimeFormatter extends JUnitFormatter
{
    /**
     * @var String
     */
    private $outputDir;

    /**
     * __construct
     *
     * @param mixed $filename
     * @param mixed $outputDir
     */
    public function __construct($filename, $outputDir)
    {
        parent::__construct($filename, $outputDir);
        $this->outputDir      = $outputDir;
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription()
    {
        return 'Creates a junit xml files for each feature';
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            FeatureTested::BEFORE   => array('beforeFeature', -50),
            FeatureTested::AFTER    => array('afterFeature', -50),
            ScenarioTested::BEFORE  => array('beforeScenario', -50),
            ScenarioTested::AFTER   => array('afterScenario', -50),
            ExampleTested::AFTER   => array('afterScenario', -50)
        );
    }

    /**
     * beforeFeature
     *
     * @param FeatureTested $event
     */
    public function beforeFeature(FeatureTested $event)
    {
        $suite = $event->getSuite();
        $feature = $event->getFeature();

        $suiteId = $suite->getName();
        $featurePathinfo = pathinfo($feature->getFile());
        $featureId = $featurePathinfo['filename'];
        $outputFile = sprintf('%s_%s.xml', $suiteId, $featureId);

        $this->printer = new FileOutputPrinter($outputFile, $this->outputDir);
        $this->xml = new \SimpleXmlElement('<testsuites></testsuites>');

        $testsuite = $this->xml->addChild('testsuite');
        $testsuite->addAttribute('name', $event->getSuite()->getName());

        $this->currentTestsuite = $testsuite = $this->xml->addChild('testsuite');
        $testsuite->addAttribute('name', $feature->getTitle());

        $this->testsuiteStats =  array(
            TestResult::PASSED    => 0,
            TestResult::SKIPPED   => 0,
            TestResult::PENDING   => 0,
            TestResult::FAILED    => 0,
        );

        $this->testsuiteTimer->start();
    }

    /**
     * afterFeature
     *
     * @param FeatureTested $event
     */
    public function afterFeature(FeatureTested $event)
    {
        parent::afterFeature($event);

        $dom = new \DOMDocument('1.0');
        $dom->preserveWhitespace = false;
        $dom->formatOutput = true;
        $dom->loadXml($this->xml->asXml());

        $this->printer->write($dom->saveXML());
    }
}
