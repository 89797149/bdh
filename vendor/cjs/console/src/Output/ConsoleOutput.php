<?php
namespace CjsConsole\Output;

use CjsConsole\Contracts\ConsoleOutputInterface;
use CjsConsole\Contracts\OutputFormatterInterface;
use CjsConsole\Contracts\OutputInterface;

/**
 *
 * $output = new ConsoleOutput();
 * $output = new StreamOutput(fopen('php://stdout', 'w'));
 */
class ConsoleOutput extends StreamOutput implements ConsoleOutputInterface
{
    private $stderr;

    public function __construct($verbosity = self::VERBOSITY_NORMAL, $decorated = null, OutputFormatterInterface $formatter = null)
    {
        $outputStream = 'php://stdout';
        if (!$this->hasStdoutSupport()) {
            $outputStream = 'php://output';
        }

        parent::__construct(fopen($outputStream, 'w'), $verbosity, $decorated, $formatter);

        $this->stderr = new StreamOutput(fopen('php://stderr', 'w'), $verbosity, $decorated, $this->getFormatter());
    }

    /**
     * {@inheritdoc}
     */
    public function setDecorated($decorated)
    {
        parent::setDecorated($decorated);
        $this->stderr->setDecorated($decorated);
    }

    /**
     * {@inheritdoc}
     */
    public function setFormatter(OutputFormatterInterface $formatter)
    {
        parent::setFormatter($formatter);
        $this->stderr->setFormatter($formatter);
    }

    /**
     * {@inheritdoc}
     */
    public function setVerbosity($level)
    {
        parent::setVerbosity($level);
        $this->stderr->setVerbosity($level);
    }

    /**
     * {@inheritdoc}
     */
    public function getErrorOutput()
    {
        return $this->stderr;
    }

    /**
     * {@inheritdoc}
     */
    public function setErrorOutput(OutputInterface $error)
    {
        $this->stderr = $error;
    }

    /**
     * Returns true if current environment supports writing console output to STDOUT.
     *
     * IBM iSeries (OS400) exhibits character-encoding issues when writing to
     * STDOUT and doesn't properly convert ASCII to EBCDIC, resulting in garbage output.
     *
     * @return bool
     */
    protected function hasStdoutSupport()
    {
        return ('OS400' != php_uname('s'));
    }
}
