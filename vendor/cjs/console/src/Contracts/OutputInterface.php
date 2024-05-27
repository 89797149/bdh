<?php
namespace CjsConsole\Contracts;

use CjsConsole\Contracts\OutputFormatterInterface;

interface OutputInterface
{
    const VERBOSITY_QUIET = 0;
    const VERBOSITY_NORMAL = 1;
    const VERBOSITY_VERBOSE = 2;
    const VERBOSITY_VERY_VERBOSE = 3;
    const VERBOSITY_DEBUG = 4;

    const OUTPUT_NORMAL = 0;
    const OUTPUT_RAW = 1;
    const OUTPUT_PLAIN = 2;

    public function write($messages, $newline = false, $type = self::OUTPUT_NORMAL);

    public function writeln($messages, $type = self::OUTPUT_NORMAL);

    public function setVerbosity($level);

    public function getVerbosity();

    public function setDecorated($decorated);

    public function isDecorated();

    public function setFormatter(OutputFormatterInterface $formatter);

    public function getFormatter();
}
