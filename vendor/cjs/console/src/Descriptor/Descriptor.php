<?php
namespace CjsConsole\Descriptor;

use CjsConsole\ConsoleApp as Application;
use CjsConsole\Command;
use CjsConsole\Input\InputArgument;
use CjsConsole\Input\InputDefinition;
use CjsConsole\Input\InputOption;
use CjsConsole\Contracts\OutputInterface;
use CjsConsole\Contracts\DescriptorInterface;

abstract class Descriptor implements DescriptorInterface
{
    private $output;

    /**
     * {@inheritdoc}
     */
    public function describe(OutputInterface $output, $object, array $options = array())
    {
        $this->output = $output;

        switch (true) {
            case $object instanceof InputArgument:
                $this->describeInputArgument($object, $options);
                break;
            case $object instanceof InputOption:
                $this->describeInputOption($object, $options);
                break;
            case $object instanceof InputDefinition:
                $this->describeInputDefinition($object, $options);
                break;
            case $object instanceof Command:
                $this->describeCommand($object, $options);
                break;
            case $object instanceof Application:
                $this->describeApplication($object, $options);
                break;
            default:
                throw new \InvalidArgumentException(sprintf('Object of type "%s" is not describable.', get_class($object)));
        }
    }

    protected function write($content, $decorated = false)
    {
        $this->output->write($content, false, $decorated ? OutputInterface::OUTPUT_NORMAL : OutputInterface::OUTPUT_RAW);
    }

    abstract protected function describeInputArgument(InputArgument $argument, array $options = array());

    abstract protected function describeInputOption(InputOption $option, array $options = array());

    abstract protected function describeInputDefinition(InputDefinition $definition, array $options = array());

    abstract protected function describeCommand(Command $command, array $options = array());

    abstract protected function describeApplication(Application $application, array $options = array());
}
