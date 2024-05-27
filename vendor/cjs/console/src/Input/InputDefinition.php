<?php
namespace CjsConsole\Input;

use CjsConsole\Descriptor\TextDescriptor;
use CjsConsole\Descriptor\XmlDescriptor;
use CjsConsole\Output\BufferedOutput;

/**
 *
 * $definition = new InputDefinition(array(
 *    new InputArgument('name', InputArgument::REQUIRED),
 *    new InputOption('foo', 'f', InputOption::VALUE_REQUIRED),
 *  ));
 *
 */
class InputDefinition
{
    private $arguments = []; //['参数名1'=>对象,]
    private $requiredCount = 0; //必须参数个数
    private $hasAnArrayArgument = false;
    private $hasOptional;
    private $options;//['选项'=>对象,]
    private $shortcuts;//['短选项名'=>全选项名,]

    public function __construct(array $definition = array())
    {
        $this->setDefinition($definition);
    }

    public function setDefinition(array $definition)
    {
        $arguments = array();
        $options = array();
        foreach ($definition as $item) {
            if ($item instanceof InputOption) {
                $options[] = $item;//选项对象
            } else {
                $arguments[] = $item;//参数对象
            }
        }
        $this->setArguments($arguments);
        $this->setOptions($options);
    }

    public function setArguments($arguments = array())
    {
        $this->arguments = array();
        $this->requiredCount = 0;
        $this->hasOptional = false;
        $this->hasAnArrayArgument = false;
        $this->addArguments($arguments);
    }

    public function addArguments($arguments = array())
    {
        if (null !== $arguments) {
            foreach ($arguments as $argument) {
                $this->addArgument($argument);
            }
        }
    }

    public function addArgument(InputArgument $argument)
    {
        if (isset($this->arguments[$argument->getName()])) {
            throw new \LogicException(sprintf('An argument with name "%s" already exists.', $argument->getName()));
        }

        if ($this->hasAnArrayArgument) {
            throw new \LogicException('Cannot add an argument after an array argument.');
        }

        if ($argument->isRequired() && $this->hasOptional) {
            throw new \LogicException('Cannot add a required argument after an optional one.');
        }

        if ($argument->isArray()) {
            $this->hasAnArrayArgument = true;
        }

        if ($argument->isRequired()) {
            ++$this->requiredCount;
        } else {
            $this->hasOptional = true;
        }

        $this->arguments[$argument->getName()] = $argument;
    }

    public function getArgument($name)
    {
        if (!$this->hasArgument($name)) {
            throw new \InvalidArgumentException(sprintf('The "%s" argument does not exist.', $name));
        }

        $arguments = is_int($name) ? array_values($this->arguments) : $this->arguments;
        return $arguments[$name];
    }

    public function hasArgument($name)
    {
        $arguments = is_int($name) ? array_values($this->arguments) : $this->arguments;

        return isset($arguments[$name]);
    }

    public function getArguments()
    {
        return $this->arguments;
    }


    public function getArgumentCount()
    {
        return $this->hasAnArrayArgument ? PHP_INT_MAX : count($this->arguments);
    }

    public function getArgumentRequiredCount()
    {
        return $this->requiredCount;
    }

    public function getArgumentDefaults()
    {
        $values = array();
        foreach ($this->arguments as $argument) {
            $values[$argument->getName()] = $argument->getDefault();
        }

        return $values;
    }

    public function setOptions($options = array())
    {
        $this->options = array();
        $this->shortcuts = array();
        $this->addOptions($options);
    }

    public function addOptions($options = array())
    {
        foreach ($options as $option) {
            $this->addOption($option);
        }
    }

    public function addOption(InputOption $option)
    {
        if (isset($this->options[$option->getName()]) && !$option->equals($this->options[$option->getName()])) {
            throw new \LogicException(sprintf('An option named "%s" already exists.', $option->getName()));
        }

        if ($option->getShortcut()) {
            foreach (explode('|', $option->getShortcut()) as $shortcut) {
                if (isset($this->shortcuts[$shortcut]) && !$option->equals($this->options[$this->shortcuts[$shortcut]])) {
                    throw new \LogicException(sprintf('An option with shortcut "%s" already exists.', $shortcut));
                }
            }
        }

        $this->options[$option->getName()] = $option;
        if ($option->getShortcut()) {
            foreach (explode('|', $option->getShortcut()) as $shortcut) {
                $this->shortcuts[$shortcut] = $option->getName();
            }
        }
    }

    public function getOption($name)
    {
        if (!$this->hasOption($name)) {
            throw new \InvalidArgumentException(sprintf('The "--%s" option does not exist.', $name));
        }
        return $this->options[$name];
    }

    public function hasOption($name)
    {
        return isset($this->options[$name]);
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function hasShortcut($name)
    {
        return isset($this->shortcuts[$name]);
    }

    public function getOptionForShortcut($shortcut)
    {
        return $this->getOption($this->shortcutToName($shortcut));
    }

    public function getOptionDefaults()
    {
        $values = array();
        foreach ($this->options as $option) {
            $values[$option->getName()] = $option->getDefault();
        }

        return $values;
    }

    private function shortcutToName($shortcut)
    {
        if (!isset($this->shortcuts[$shortcut])) {
            throw new \InvalidArgumentException(sprintf('The "-%s" option does not exist.', $shortcut));
        }

        return $this->shortcuts[$shortcut];
    }

    public function getSynopsis()
    {
        $elements = array();
        foreach ($this->getOptions() as $option) {
            $shortcut = $option->getShortcut() ? sprintf('-%s|', $option->getShortcut()) : '';
            $elements[] = sprintf('['.($option->isValueRequired() ? '%s--%s="..."' : ($option->isValueOptional() ? '%s--%s[="..."]' : '%s--%s')).']', $shortcut, $option->getName());
        }

        foreach ($this->getArguments() as $argument) {
            $elements[] = sprintf($argument->isRequired() ? '%s' : '[%s]', $argument->getName().($argument->isArray() ? '1' : ''));

            if ($argument->isArray()) {
                $elements[] = sprintf('... [%sN]', $argument->getName());
            }
        }

        return implode(' ', $elements);
    }

    public function asText()
    {
        $descriptor = new TextDescriptor();
        $output = new BufferedOutput(BufferedOutput::VERBOSITY_NORMAL, true);
        $descriptor->describe($output, $this, array('raw_output' => true));

        return $output->fetch();
    }

    public function asXml($asDom = false)
    {
        $descriptor = new XmlDescriptor();

        if ($asDom) {
            return $descriptor->getInputDefinitionDocument($this);
        }

        $output = new BufferedOutput();
        $descriptor->describe($output, $this);

        return $output->fetch();
    }

}
