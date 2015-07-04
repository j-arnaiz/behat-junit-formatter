<?php

namespace jarnaiz\JUnitFormatter\Printer;

use Behat\Testwork\Output\Exception\BadOutputPathException;
use Behat\Testwork\Output\Printer\OutputPrinter as PrinterInterface;


/**
 * Class: FileOutputPrinter
 *
 * @see PrinterInterface
 */
class FileOutputPrinter implements PrinterInterface
{

    /**
     * @var string
     */
    private $path;

    /**
     * @var string
     */
    private $filename;

    /**
     * __construct
     *
     * @param string $filename
     * @param string $outputDir
     */
    public function __construct($filename, $outputDir)
    {
        $this->filename = $filename;
        $this->setOutputPath($outputDir);
    }

    /**
     * setOutputPath
     *
     * @param string $outpath
     */
    public function setOutputPath($outpath)
    {
        if (!file_exists($outpath)) {
            if (!mkdir($outpath, 0755, true)) {
                throw new BadOutputPathException(
                    sprintf(
                        'Output path %s does not exist and could not be created!',
                        $outpath
                    ),
                    $outpath
                );
            }
        } else {
            if (!is_dir(realpath($outpath))) {
                throw new BadOutputPathException(
                    sprintf(
                        'The argument to `output` is expected to the a directory, but got %s!',
                        $outpath
                    ),
                    $outpath
                );
            }
        }
        $this->path = $outpath;
    }

    /**
     * Returns output path
     *
     * @return string path
     */
    public function getOutputPath()
    {
        return $this->path;
    }

    /**
     * @param array $styles
     */
    public function setOutputStyles(array $styles)
    {
    }

    /**
     * @return array
     */
    public function getOutputStyles()
    {
    }

    /**
     * @param Boolean $decorated
     */
    public function setOutputDecorated($decorated)
    {
    }

    /**
     * @return null|Boolean
     */
    public function isOutputDecorated()
    {
        return true;
    }

    /**
     * @param integer $level
     */
    public function setOutputVerbosity($level)
    {
    }

    /**
     * @return integer
     */
    public function getOutputVerbosity()
    {
        return 0;
    }

    /**
     * write
     *
     * @param mixed $messages
     * @param mixed $append
     */
    public function write($messages, $append = false)
    {
        $file = $this->getOutputPath() . DIRECTORY_SEPARATOR . $this->filename;

        if ($append) {
            file_put_contents($file, $messages, FILE_APPEND);
        } else {
            file_put_contents($file, $messages);
        }
    }


    /**
     * writeln
     *
     * @param array $messages
     */
    public function writeln($messages = '')
    {
        $this->write($messages, true);
    }

    /**
     * flush
     *
     */
    public function flush()
    {
    }
}
