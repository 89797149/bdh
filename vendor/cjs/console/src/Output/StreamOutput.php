<?php
namespace CjsConsole\Output;

use CjsConsole\Contracts\OutputFormatterInterface;

/**
 * $output = new StreamOutput(fopen('php://stdout', 'w'));
 * $output = new StreamOutput(fopen('/path/to/output.log', 'a', false));
 *
 */
class StreamOutput extends Output
{
    private $stream;

    public function __construct($stream, $verbosity = self::VERBOSITY_NORMAL, $decorated = null, OutputFormatterInterface $formatter = null)
    {
        if (!is_resource($stream) || 'stream' !== get_resource_type($stream)) {
            throw new \InvalidArgumentException('The StreamOutput class needs a stream as its first argument.');
        }
        $this->stream = $stream;
        if (null === $decorated) {
            $decorated = $this->hasColorSupport();
        }
        parent::__construct($verbosity, $decorated, $formatter);
    }

    public function getStream()
    {
        return $this->stream;
    }

    /**
     * {@inheritdoc}
     */
    protected function doWrite($message, $newline)
    {
        if (false === @fwrite($this->stream, $message.($newline ? PHP_EOL : ''))) {
            // should never happen
            throw new \RuntimeException('Unable to write output.');
        }
        fflush($this->stream);
    }

    protected function hasColorSupport()
    {
        if (DIRECTORY_SEPARATOR == '\\') {
            return false !== getenv('ANSICON') || 'ON' === getenv('ConEmuANSI');
        }
        return function_exists('posix_isatty') && @posix_isatty($this->stream);
    }

}
