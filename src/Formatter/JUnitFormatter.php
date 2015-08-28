<?php

namespace dizzy7\JUnitFormatter\Formatter;

use Behat\Testwork\Output\Printer\OutputPrinter;
use Behat\Testwork\Tester\Result\TestResult;
use Behat\Behat\EventDispatcher\Event\FeatureTested;
use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Behat\Behat\EventDispatcher\Event\ExampleTested;
use Behat\Testwork\Output\Formatter;
use Behat\Testwork\Counter\Timer;
use dizzy7\JUnitFormatter\Printer\FileOutputPrinter;

/**
 * Class: JUnitFormatter
 *
 * @see Formatter
 */
class JUnitFormatter implements Formatter
{
    const FORMATTER_NAME = 'junit';

    /**
     * printer
     *
     * @var mixed
     */
    protected $printer;

    /**
     * @var array
     */
    protected $parameters = array();

    /**
     * @var \SimpleXmlElement
     */
    protected $xml;

    /**
     * @var \SimpleXmlElement
     */
    protected $currentTestsuite;

    /**
     * @var int[]
     */
    protected $testsuiteStats;

    /**
     * @var \SimpleXmlElement
     */
    protected $currentTestcase;

    /**
     * @var Timer
     */
    protected $testsuiteTimer;

    /**
     * @var Timer
     */
    protected $testcaseTimer;

    /**
     * @var String
     */
    protected $currentOutlineTitle;

    /**
     * @var String
     */
    protected $outputDir;

    /**
     * __construct
     *
     * @param mixed $filename
     * @param mixed $outputDir
     */
    public function __construct($filename, $outputDir)
    {
        $this->printer        = new FileOutputPrinter($filename, $outputDir);
        $this->testsuiteTimer = new Timer();
        $this->testcaseTimer  = new Timer();
        $this->outputDir      = $outputDir;
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
     * @return void
     */
    public function afterFeature()
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
}
