<?php
namespace CjsConsole\Output;

use CjsConsole\Formatter\OutputFormatter;
use CjsConsole\Contracts\OutputFormatterInterface;
use CjsConsole\Contracts\OutputInterface;
/**
 * NullOutput suppresses all output.
 *
 * $output = new NullOutput();
 */
class NullOutput implements OutputInterface
{
    /**
     * {@inheritdoc}
     */
    public function setFormatter(OutputFormatterInterface $formatter)
    {
        // do nothing
    }

    /**
     * {@inheritdoc}
     */
    public function getFormatter()
    {
        // to comply with the interface we must return a OutputFormatterInterface
        return new OutputFormatter();
    }

    /**
     * {@inheritdoc}
     */
    public function setDecorated($decorated)
    {
        // do nothing
    }

    /**
     * {@inheritdoc}
     */
    public function isDecorated()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function setVerbosity($level)
    {
        // do nothing
    }

    /**
     * {@inheritdoc}
     */
    public function getVerbosity()
    {
        return self::VERBOSITY_QUIET;
    }

    public function isQuiet()
    {
        return true;
    }

    public function isVerbose()
    {
        return false;
    }

    public function isVeryVerbose()
    {
        return false;
    }

    public function isDebug()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function writeln($messages, $type = self::OUTPUT_NORMAL)
    {
        // do nothing
    }

    /**
     * {@inheritdoc}
     */
    public function write($messages, $newline = false, $type = self::OUTPUT_NORMAL)
    {
        // do nothing
    }
}
