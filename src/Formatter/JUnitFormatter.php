<?php

namespace jarnaiz\JUnitFormatter\Formatter;

use Behat\Behat\Tester\Result\StepResult as TestResult;
use Behat\Behat\EventDispatcher\Event\FeatureTested;
use Behat\Behat\EventDispatcher\Event\ScenarioTested;
use Behat\Behat\EventDispatcher\Event\StepTested;
use Behat\Behat\EventDispatcher\Event\OutlineTested;
use Behat\Behat\EventDispatcher\Event\ExampleTested;
use Behat\Testwork\EventDispatcher\Event\SuiteTested;
use Behat\Testwork\EventDispatcher\Event\ExerciseCompleted;
use Behat\Testwork\Output\Formatter;
use Behat\Testwork\Counter\Timer;
use jarnaiz\JUnitFormatter\Printer\FileOutputPrinter;

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
        return 'Creates a junit xml file';
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
            ExerciseCompleted::BEFORE => array('beforeExercise', -50),
            ExerciseCompleted::AFTER => array('afterExercise', -50),
            SuiteTested::BEFORE     => array('beforeSuite', -50),
            SuiteTested::AFTER      => array('afterSuite', -50),
            FeatureTested::BEFORE   => array('beforeFeature', -50),
            FeatureTested::AFTER    => array('afterFeature', -50),
            ScenarioTested::BEFORE  => array('beforeScenario', -50),
            ScenarioTested::AFTER   => array('afterScenario', -50),
            StepTested::AFTER       => array('afterStep', -50),
            OutlineTested::BEFORE   => array('beforeOutline', -50),
            ExampleTested::BEFORE  => array('beforeExample', -50),
            ExampleTested::AFTER   => array('afterScenario', -50)
        );
    }

    /**
     * @param ExerciseCompleted $event
     */
    public function beforeExercise(ExerciseCompleted $event)
    {
        $this->xml = new \SimpleXmlElement('<testsuites></testsuites>');
    }

    /**
     * beforeSuite
     *
     * @param SuiteTested $event
     */
    public function beforeSuite(SuiteTested $event)
    {
        $suite = $event->getSuite();

        $testsuite = $this->xml->addChild('testsuite');
        $testsuite->addAttribute('name', $suite->getName());
        $testsuite->addAttribute('tests', 0);
    }

    /**
     * beforeFeature
     *
     * @param FeatureTested $event
     */
    public function beforeFeature(FeatureTested $event)
    {
        $feature = $event->getFeature();

        $this->currentTestsuite = $testsuite = $this->xml->addChild('testsuite');
        $testsuite->addAttribute('name', $feature->getTitle());

        $this->testsuiteStats =  array(
            TestResult::PASSED    => 0,
            TestResult::SKIPPED   => 0,
            TestResult::PENDING   => 0,
            TestResult::FAILED    => 0,
            TestResult::UNDEFINED => 0,
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
     * afterStep
     *
     * @param mixed $event
     */
    public function afterStep($event)
    {
        $code = $event->getTestResult()->getResultCode();
        if(TestResult::FAILED === $code) {
            if ($event->getTestResult()->hasException()) {
                $failureNode = $this->currentTestcase->addChild('failure');

                $failureText = $event->getStep()->getKeyword() . " " . $event->getStep()->getText() . ":\n\n" . $event->getTestResult()->getException()->getMessage();

                // add cdata
                $node = dom_import_simplexml($failureNode);
                $no = $node->ownerDocument;
                $node->appendChild($no->createCDATASection($failureText));

                $failureNode->addAttribute('type', \get_class($event->getTestResult()->getException()));
            }
        }
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
            TestResult::UNDEFINED => 'undefined',
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
        $testsuite['tests'] = array_sum($this->testsuiteStats);
        $testsuite->addAttribute('failures', $this->testsuiteStats[TestResult::FAILED]);
        $testsuite->addAttribute('skipped', $this->testsuiteStats[TestResult::SKIPPED]);
        $testsuite->addAttribute('errors', $this->testsuiteStats[TestResult::PENDING]);
        $testsuite->addAttribute('time', \round($this->testsuiteTimer->getTime(), 3));
    }

    /**
     * afterSuite
     *
     * @param SuiteTested $event
     */
    public function afterSuite(SuiteTested $event)
    {
    }

    /**
     * @param ExerciseCompleted $event
     */
    public function afterExercise(ExerciseCompleted $event)
    {
        $dom = new \DOMDocument('1.0');
        $dom->preserveWhitespace = false;
        $dom->formatOutput = true;
        $dom->loadXml($this->xml->asXml());

        $this->printer->write($dom->saveXML());
    }
}
