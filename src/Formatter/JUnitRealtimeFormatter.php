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
class JUnitRealtimeFormatter implements Formatter
{
    const FORMATTER_NAME = 'junit';

    /**
     * printer
     *
     * @var mixed
     */
    private $printer;

    /**
     * @var array
     */
    private $parameters = array();

    /**
     * @var \SimpleXmlElement
     */
    private $xml;

    /**
     * @var \SimpleXmlElement
     */
    private $currentTestsuite;

    /**
     * @var int[]
     */
    private $testsuiteStats;

    /**
     * @var \SimpleXmlElement
     */
    private $currentTestcase;

    /**
     * @var Timer
     */
    private $testsuiteTimer;

    /**
     * @var Timer
     */
    private $testcaseTimer;

    /**
     * @var String
     */
    private $currentOutlineTitle;

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
    public function __construct($outputDir)
    {
        $this->printer        = new FileOutputPrinter($outputDir, 'null.xml');
        $this->outputDir      = $outputDir;
        $this->testsuiteTimer = new Timer();
        $this->testcaseTimer  = new Timer();
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return self::FORMATTER_NAME;
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
    public function setParameter($name, $value)
    {
        $this->parameters[$name] = $value;
    }

    /**
     * {@inheritDoc}
     */
    public function getParameter($name)
    {
        return isset($this->parameters[$name]) ? $this->parameters[$name] : null;
    }

    /**
     * getOutputPrinter
     *
     * @return OutputPrinter
     */
    public function getOutputPrinter()
    {
        return $this->printer;
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
     * beforeScenario
     *
     * @param ScenarioTested $event
     *
     * @return void
     */
    public function beforeScenario(ScenarioTested $event)
    {
        $this->currentTestcase = $this->currentTestsuite->addChild('testcase');
        $this->currentTestcase->addAttribute('name', $event->getScenario()->getTitle());

        $this->testcaseTimer->start();
    }

    /**
     * beforeOutline
     *
     * @param OutlineTested $event
     *
     * @return void
     */
    public function beforeOutline(OutlineTested $event)
    {
        $this->currentOutlineTitle = $event->getOutline()->getTitle();
    }

    /**
     * beforeExample
     *
     * @param ScenarioTested $event
     *
     * @return void
     */
    public function beforeExample(ScenarioTested $event)
    {
        $this->currentTestcase = $this->currentTestsuite->addChild('testcase');
        $this->currentTestcase->addAttribute('name', $this->currentOutlineTitle . ' Line #' . $event->getScenario()->getLine());

        $this->testcaseTimer->start();
    }

    /**
     * afterScenario
     *
     * @param mixed $event
     */
    public function afterScenario($event)
    {
        $this->testcaseTimer->stop();
        $code = $event->getTestResult()->getResultCode();
        $testResultString = array(
            TestResult::PASSED    => 'passed',
            TestResult::SKIPPED   => 'skipped',
            TestResult::PENDING   => 'pending',
            TestResult::FAILED    => 'failed',
        );

        $this->testsuiteStats[$code]++;

        $this->currentTestcase->addAttribute('time', \round($this->testcaseTimer->getTime(), 3));
        $this->currentTestcase->addAttribute('status', $testResultString[$code]);
    }

    /**
     * afterFeature
     *
     * @param FeatureTested $event
     */
    public function afterFeature(FeatureTested $event)
    {
        $this->testsuiteTimer->stop();
        $testsuite = $this->currentTestsuite;
        $testsuite->addAttribute('tests', array_sum($this->testsuiteStats));
        $testsuite->addAttribute('failures', $this->testsuiteStats[TestResult::FAILED]);
        $testsuite->addAttribute('skipped', $this->testsuiteStats[TestResult::SKIPPED]);
        $testsuite->addAttribute('errors', $this->testsuiteStats[TestResult::PENDING]);
        $testsuite->addAttribute('time', \round($this->testsuiteTimer->getTime(), 3));

        $dom = new \DOMDocument('1.0');
        $dom->preserveWhitespace = false;
        $dom->formatOutput = true;
        $dom->loadXml($this->xml->asXml());

        $this->printer->write($dom->saveXML());
    }
}
