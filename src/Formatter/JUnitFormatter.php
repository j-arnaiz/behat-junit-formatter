<?php

namespace dizzy7\JUnitFormatter\Formatter;

use Behat\Behat\EventDispatcher\Event\AfterScenarioTested;
use Behat\Behat\EventDispatcher\Event\AfterStepTested;
use Behat\Behat\EventDispatcher\Event\StepTested;
use Behat\Behat\Tester\Result\ExecutedStepResult;
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
     * @var string
     */
    protected $currentOutlineTitle;

    /**
     * @var string
     */
    protected $outputDir;

    /**
     * @var array|null
     */
    protected $lastStepFailure;

    /**
     * @var \Exception|null
     */
    protected $lastStepFailureException;

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
            ExampleTested::AFTER    => array('afterScenario', -50),
            StepTested::AFTER       => array('afterStep', -50)
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
        $this->xml = new \SimpleXmlElement('<?xml version="1.0" encoding="utf-8"?><testsuites></testsuites>');

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

        $this->printer->write($this->xml->asXML());
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
    public function afterScenario(AfterScenarioTested $event)
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

        if ($this->lastStepFailure) {
            $failureNode = $this->currentTestcase->addChild('failure', $this->lastStepFailureException->getMessage());
            $failureNode->addAttribute('message', $this->lastStepFailure);
        }
    }

    public function afterStep(AfterStepTested $event)
    {
        /** @var ExecutedStepResult $result */
        $result = $event->getTestResult();
        if ($result->getResultCode() === TestResult::FAILED) {
            $exception = $result->getException();
            if ($exception) {
                $this->lastStepFailure = sprintf(
                    '%s:%d',
                    $event->getFeature()->getFile(),
                    $event->getStep()->getLine()
                );
                $this->lastStepFailureException = $exception;
            }
        } elseif ($result->getResultCode() === TestResult::PASSED) {
            $this->lastStepFailure = null;
            $this->lastStepFailureException = null;
        }
    }
}
