<?php
namespace CjsConsole;

use CjsConsole\Input\InputDefinition;
use CjsConsole\Contracts\OutputInterface;
use CjsConsole\Contracts\InputInterface;
use CjsConsole\Input\InputOption;
use CjsConsole\Input\InputArgument;

class Command{

    protected $name;
    private $definition;
    private $processTitle;
    private $aliases = array();
    private $help;
    private $description;
    private $ignoreValidationErrors = false;
    private $applicationDefinitionMerged = false;
    private $applicationDefinitionMergedWithArgs = false;
    private $code;
    private $synopsis;
    private $helperSet;

    protected $app; //这是console app对象

    protected $input;
    protected $output;


    public function __construct($name = null)
    {
        $this->definition = new InputDefinition();

        if (null !== $name) {
            $this->setName($name);
        }

        $this->configure();

        if (!$this->name) {
            throw new \LogicException(sprintf('The command defined in "%s" cannot have an empty name.', get_class($this)));
        }

        $this->specifyParameters();
    }

    protected function configure()
    {
    }

    public function run($input, $output)
    {
        $this->input = $input;
        $this->output = $output;
        $this->getSynopsis();

        // add the application arguments and options
        $this->mergeApplicationDefinition();

        // bind the input against the command specific arguments/options
        try {
            $input->bind($this->definition);
        } catch (\Exception $e) {
            if (!$this->ignoreValidationErrors) {
                throw $e;
            } else {
                $output->writeln(sprintf('<comment>%s</comment>', $e->getMessage()));
            }
        }

        $this->initialize($input, $output);

        if (null !== $this->processTitle) {
            if (function_exists('cli_set_process_title')) {
                cli_set_process_title($this->processTitle);
            } elseif (function_exists('setproctitle')) {
                setproctitle($this->processTitle);
            } elseif (OutputInterface::VERBOSITY_VERY_VERBOSE === $output->getVerbosity()) {
                $output->writeln('<comment>Install the proctitle PECL to be able to change the process title.</comment>');
            }
        }

        if ($input->isInteractive()) {
            $this->interact($input, $output);
        }

        $input->validate();

        if ($this->code) {
            $statusCode = call_user_func($this->code, $input, $output);
        } else {
            $statusCode = $this->execute($input, $output);
        }

        return is_numeric($statusCode) ? (int) $statusCode : 0;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
    }

    public function mergeApplicationDefinition($mergeArgs = true)
    {
        if (null === $this->app || (true === $this->applicationDefinitionMerged && ($this->applicationDefinitionMergedWithArgs || !$mergeArgs))) {
            return;
        }

        if ($mergeArgs) {//参数
            $currentArguments = $this->definition->getArguments();
            $this->definition->setArguments($this->app->getDefinition()->getArguments());
            $this->definition->addArguments($currentArguments);
        }
        //选项
        $this->definition->addOptions($this->app->getDefinition()->getOptions());

        $this->applicationDefinitionMerged = true;
        if ($mergeArgs) {
            $this->applicationDefinitionMergedWithArgs = true;
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $method = method_exists($this, 'handle') ? 'handle' : 'fire';
        return call_user_func_array([$this, $method], []);
        //throw new \LogicException('You must override the execute() method in the concrete command class.');
    }

    public function ignoreValidationErrors()
    {
        $this->ignoreValidationErrors = true;
    }


    public function comment($string){

        $this->output->writeln("<comment>$string</comment>");
    }


    public function info($string)
    {
        $this->output->writeln("<info>$string</info>");
    }


    public function line($string)
    {
        $this->output->writeln($string);
    }


    public function question($string)
    {
        $this->output->writeln("<question>$string</question>");
    }
    public function error($string)
    {
        $this->output->writeln("<error>$string</error>");
    }

    protected function getArguments()
    {
        return array();
    }

    protected function getOptions()
    {
        return array();
    }

    public function getOutput()
    {
        return $this->output;
    }

    public function setApplication($application = null)
    {
        $this->app = $application;
        if ($application) {
            //$this->setHelperSet($application->getHelperSet());
        } else {
            $this->helperSet = null;
        }
        return $this;
    }

    public function getApplication()
    {
        return $this->app;
    }

    public function setHelperSet($helperSet)
    {
        $this->helperSet = $helperSet;
    }

    public function getHelperSet()
    {
        return $this->helperSet;
    }

    public function getProcessedHelp()
    {
        $name = $this->name;
        $placeholders = array(
                                '%command.name%',
                                '%command.full_name%',
                            );
        $replacements = array(
                            $name,
                            $_SERVER['PHP_SELF'].' '.$name,
                        );
        return str_replace($placeholders, $replacements, $this->getHelp());
    }

    public function setCode($code)
    {
        if (!is_callable($code)) {
            throw new \InvalidArgumentException('Invalid callable provided to Command::setCode.');
        }
        $this->code = $code;
        return $this;
    }

    public function setProcessTitle($title)
    {
        $this->processTitle = $title;
        return $this;
    }

    public function setName($name)
    {
        $this->validateName($name);
        $this->name = $name;
        return $this;
    }

    protected function validateName($name)
    {
        if (!preg_match('/^[^\:]++(\:[^\:]++)*$/', $name)) {
            throw new \InvalidArgumentException(sprintf('Command name "%s" is invalid.', $name));
        }
    }

    public function getName()
    {
        return $this->name;
    }

    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setHelp($help)
    {
        $this->help = $help;
        return $this;
    }

    public function getHelp()
    {
        return $this->help;
    }


    public function setAliases($aliases)
    {
        if (!is_array($aliases) && !$aliases instanceof \Traversable) {
            throw new \InvalidArgumentException('$aliases must be an array or an instance of \Traversable');
        }

        foreach ($aliases as $alias) {
            $this->validateName($alias);
        }

        $this->aliases = $aliases;

        return $this;
    }

    public function getAliases()
    {
        return $this->aliases;
    }

    public function getNativeDefinition()
    {
        return $this->getDefinition();
    }

    public function getDefinition()
    {
        return $this->definition;
    }

    public function setDefinition($definition)
    {
        if ($definition instanceof InputDefinition) {
            $this->definition = $definition;
        } else {
            $this->definition->setDefinition($definition);
        }

        $this->applicationDefinitionMerged = false;

        return $this;
    }


    public function isEnabled()
    {
        return true;
    }


    public function getSynopsis()
    {
        if (null === $this->synopsis) {
            $this->synopsis = trim(sprintf('%s %s', $this->name, $this->definition->getSynopsis()));
        }

        return $this->synopsis;
    }

    public function addArgument($name, $mode = null, $description = '', $default = null)
    {
        $this->definition->addArgument(new InputArgument($name, $mode, $description, $default));

        return $this;
    }

    public function addOption($name, $shortcut = null, $mode = null, $description = '', $default = null)
    {
        $this->definition->addOption(new InputOption($name, $shortcut, $mode, $description, $default));

        return $this;
    }


    protected function specifyParameters()
    {
        foreach ($this->getArguments() as $arguments)
        {
            call_user_func_array(array($this, 'addArgument'), $arguments);
        }

        foreach ($this->getOptions() as $options)
        {
            call_user_func_array(array($this, 'addOption'), $options);
        }
    }

    //获取cli参数值
    public function argument($key = null)
    {
        if (is_null($key)) return $this->input->getArguments();

        return $this->input->getArgument($key);
    }

    //获取cli选项值
    public function option($key = null)
    {
        if (is_null($key)) return $this->input->getOptions();

        return $this->input->getOption($key);
    }


}