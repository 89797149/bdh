<?php
namespace CjsConsole;

use CjsConsole\Scheduling\Schedule;
use CjsConsole\Input\ArgvInput;
use CjsConsole\Input\ArrayInput;
use CjsConsole\Output\ConsoleOutput;
use CjsConsole\Input\InputDefinition;
use CjsConsole\Input\InputArgument;
use CjsConsole\Input\InputOption;
use CjsConsole\Contracts\OutputInterface;
use CjsConsole\Contracts\InputInterface;
use CjsConsole\Command\ListCommand;
use CjsConsole\Command\HelpCommand;

class ConsoleApp {

    protected static $instance = null;
    protected $commands = [];
    protected $commandConfig = [];

    protected $name;
    protected $version;
    protected $defaultCommand;
    protected $wantHelps = false; //标记是否获取帮助

    private $autoExit = false;
    private $catchExceptions = false;
    protected $definition;

    public static function getInstance($name = 'UNKNOWN', $version = 'UNKNOWN') {
        if(static::$instance) {
            return static::$instance;
        }
        static::$instance = new static($name, $version);
        return static::$instance;
    }

    protected function __construct($name = 'UNKNOWN', $version = 'UNKNOWN')
    {
        $this->name = $name;
        $this->version = $version;
        $this->defaultCommand = 'list';

        $this->definition = $this->getDefaultInputDefinition();
        $this->init();
    }


    protected function init() {
        foreach ($this->getDefaultCommands() as $command) {
            $this->add($command);
        }

    }

    public function setCatchExceptions($bool = false) {
        $this->catchExceptions = $bool;
        return $this;
    }

    public function getCatchExceptions() {
        return $this->catchExceptions;
    }

    public function setCommandConfig($command) {
        $this->commandConfig = array_merge($this->commandConfig, (array)$command);
        return $this;
    }

    public function setCommands(Command $command) {

        $this->commands[$command->getName()] = $command;
        foreach ($command->getAliases() as $alias) {
            $this->commands[$alias] = $command;
        }
        return $command;
    }

    public function getCommands() {
        return $this->commands;
    }

    public function resolveCommands($commands)
    {
        $commands = is_array($commands) ? $commands : func_get_args();
        foreach ($commands as $command)
        {//循环每个命令
            $commandObj = new $command();
            $this->add($commandObj);
        }
        return $this;
    }

    public function add(Command $command)
    {
        $command->setApplication($this);
        if (!$command->isEnabled()) {//命令未开启
            $command->setApplication(null); //清空console app对象
            return;
        }
        if (null === $command->getDefinition()) {
            throw new \LogicException(sprintf('Command class "%s" is not correctly initialized. You probably forgot to call the parent constructor.', get_class($command)));
        }
        $this->commands[$command->getName()] = $command;
        foreach ($command->getAliases() as $alias) {
            $this->commands[$alias] = $command;
        }
        return $command;
    }

    public function run($input = null , $output = null) {
        if(ConsoleConfig::getInstance()->isDebug()) {
            \CjsConsole\debug('开始实例化所有command类...' . PHP_EOL);
        }
        $this->resolveCommands($this->commandConfig);

        if (null === $input) {
            $input = new ArgvInput();
        }
        if (null === $output) {
            $output = new ConsoleOutput();
        }

        $this->configureIO($input, $output);

        try {
            $exitCode = $this->doRun($input, $output);
        } catch (\Exception $e) {
            //echo $e->getMessage();
            if (!$this->catchExceptions) {
                throw $e;
            }

            $exitCode = $e->getCode();
            if (is_numeric($exitCode)) {
                $exitCode = (int) $exitCode;
                if (0 === $exitCode) {
                    $exitCode = 1;
                }
            } else {
                $exitCode = 1;
            }
        }

        if ($this->autoExit) {
            if ($exitCode > 255) {
                $exitCode = 255;
            }
            exit($exitCode);
        }

        return $exitCode;
    }

    public function doRun(InputInterface $input, OutputInterface $output)
    {
        if (true === $input->hasParameterOption(array('--version', '-V'))) {
            $output->writeln($this->getLongVersion());
            return 0;
        }
        $name = $this->getCommandName($input);//分析命令行 获取要执行的命令
        if (true === $input->hasParameterOption(array('--help', '-h'))) {
            if (!$name) {
                $name = 'help';
                $input = new ArrayInput(array('command' => 'help'));
            } else {
                $this->wantHelps = true;
            }
        }

        if (!$name) {
            $name = $this->defaultCommand;
            $input = new ArrayInput(array('command' => $this->defaultCommand));
        }

        $command = $this->find($name); //查找匹配命令

        $this->runningCommand = $command;
        $exitCode = $this->doRunCommand($command, $input, $output); //执行命令
        $this->runningCommand = null;

        return $exitCode;
    }

    protected function doRunCommand(Command $command, InputInterface $input, OutputInterface $output)
    {
//        foreach ($command->getHelperSet() as $helper) {
//            if ($helper instanceof InputAwareInterface) {
//                $helper->setInput($input);
//            }
//        }

        return $command->run($input, $output);

    }

    public function find($name)
    {
        $allCommands = array_keys($this->commands);//所有命令
        $expr = preg_replace_callback('{([^:]+|)}', function ($matches) { return preg_quote($matches[1]).'[^:]*'; }, $name);
        $commands = preg_grep('{^'.$expr.'}', $allCommands);

        if (empty($commands) || count(preg_grep('{^'.$expr.'$}', $commands)) < 1) {
            if (false !== $pos = strrpos($name, ':')) {// make:controller
                $this->findNamespace(substr($name, 0, $pos));
            }
            $message = sprintf('Command "%s" is not defined.', $name);
            if ($alternatives = $this->findAlternatives($name, $allCommands, array())) {
                if (1 == count($alternatives)) {
                    $message .= "\n\nDid you mean this?\n    ";
                } else {
                    $message .= "\n\nDid you mean one of these?\n    ";
                }
                $message .= implode("\n    ", $alternatives);
            }
            throw new \InvalidArgumentException($message);
        }

        // filter out aliases for commands which are already on the list
        if (count($commands) > 1) {
            $commandList = $this->commands;
            $commands = array_filter($commands, function ($nameOrAlias) use ($commandList, $commands) {
                $commandName = $commandList[$nameOrAlias]->getName();

                return $commandName === $nameOrAlias || !in_array($commandName, $commands);
            });
        }

        $exact = in_array($name, $commands, true);
        if (count($commands) > 1 && !$exact) {
            $suggestions = $this->getAbbreviationSuggestions(array_values($commands));
            throw new \InvalidArgumentException(sprintf('Command "%s" is ambiguous (%s).', $name, $suggestions));
        }

        return $this->get($exact ? $name : reset($commands));
    }

    public function has($name)
    {
        return isset($this->commands[$name]);
    }

    public function getNamespaces()
    {
        $namespaces = array();
        foreach ($this->commands as $command) {
            $namespaces = array_merge($namespaces, $this->extractAllNamespaces($command->getName()));

            foreach ($command->getAliases() as $alias) {
                $namespaces = array_merge($namespaces, $this->extractAllNamespaces($alias));
            }
        }
        return array_values(array_unique(array_filter($namespaces)));
    }

    public function findNamespace($namespace)
    {
        $allNamespaces = $this->getNamespaces();
        $expr = preg_replace_callback('{([^:]+|)}', function ($matches) { return preg_quote($matches[1]).'[^:]*'; }, $namespace);
        $namespaces = preg_grep('{^'.$expr.'}', $allNamespaces);

        if (empty($namespaces)) {
            $message = sprintf('There are no commands defined in the "%s" namespace.', $namespace);

            if ($alternatives = $this->findAlternatives($namespace, $allNamespaces, array())) {
                if (1 == count($alternatives)) {
                    $message .= "\n\nDid you mean this?\n    ";
                } else {
                    $message .= "\n\nDid you mean one of these?\n    ";
                }
                $message .= implode("\n    ", $alternatives);
            }
            throw new \InvalidArgumentException($message);
        }

        $exact = in_array($namespace, $namespaces, true);
        if (count($namespaces) > 1 && !$exact) {
            throw new \InvalidArgumentException(sprintf('The namespace "%s" is ambiguous (%s).', $namespace, $this->getAbbreviationSuggestions(array_values($namespaces))));
        }

        return $exact ? $namespace : reset($namespaces);
    }

    public function get($name)
    {
        if (!isset($this->commands[$name])) {
            throw new \InvalidArgumentException(sprintf('The command "%s" does not exist.', $name));
        }
        $command = $this->commands[$name];
        if ($this->wantHelps) {
            $this->wantHelps = false;
            $helpCommand = $this->get('help'); //命令帮助
            $helpCommand->setCommand($command);
            return $helpCommand;
        }

        return $command;
    }

    private function findAlternatives($name, $collection)
    {
        $threshold = 1e3;
        $alternatives = array();

        $collectionParts = array();
        foreach ($collection as $item) {
            $collectionParts[$item] = explode(':', $item);
        }

        foreach (explode(':', $name) as $i => $subname) {
            foreach ($collectionParts as $collectionName => $parts) {
                $exists = isset($alternatives[$collectionName]);
                if (!isset($parts[$i]) && $exists) {
                    $alternatives[$collectionName] += $threshold;
                    continue;
                } elseif (!isset($parts[$i])) {
                    continue;
                }

                $lev = levenshtein($subname, $parts[$i]);
                if ($lev <= strlen($subname) / 3 || '' !== $subname && false !== strpos($parts[$i], $subname)) {
                    $alternatives[$collectionName] = $exists ? $alternatives[$collectionName] + $lev : $lev;
                } elseif ($exists) {
                    $alternatives[$collectionName] += $threshold;
                }
            }
        }

        foreach ($collection as $item) {
            $lev = levenshtein($name, $item);
            if ($lev <= strlen($name) / 3 || false !== strpos($item, $name)) {
                $alternatives[$item] = isset($alternatives[$item]) ? $alternatives[$item] - $lev : $lev;
            }
        }

        $alternatives = array_filter($alternatives, function ($lev) use ($threshold) { return $lev < 2*$threshold; });
        asort($alternatives);

        return array_keys($alternatives);
    }


    protected function getDefaultInputDefinition()
    {
        return new InputDefinition(array(
            new InputArgument('command', InputArgument::REQUIRED, 'The command to execute'),

            new InputOption('--help',           '-h', InputOption::VALUE_NONE, 'Display this help message'),
            new InputOption('--quiet',          '-q', InputOption::VALUE_NONE, 'Do not output any message'),
            new InputOption('--verbose', '-v|vv|vvv', InputOption::VALUE_NONE, 'Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug'),
            new InputOption('--version',        '-V', InputOption::VALUE_NONE, 'Display this application version'),
            new InputOption('--ansi',           '',   InputOption::VALUE_NONE, 'Force ANSI output'),
            new InputOption('--no-ansi',        '',   InputOption::VALUE_NONE, 'Disable ANSI output'),
            new InputOption('--no-interaction', '-n', InputOption::VALUE_NONE, 'Do not ask any interactive question'),
        ));
    }

    public function setDefinition(InputDefinition $definition)
    {
        $this->definition = $definition;
    }

    public function getDefinition()
    {
        return $this->definition;
    }

    protected function configureIO($input, $output)
    {
        if (true === $input->hasParameterOption(array('--ansi'))) {
            $output->setDecorated(true);
        } elseif (true === $input->hasParameterOption(array('--no-ansi'))) {
            $output->setDecorated(false);
        }

        if (true === $input->hasParameterOption(array('--no-interaction', '-n'))) {
            $input->setInteractive(false);
        } elseif (function_exists('posix_isatty')){ //&& $this->getHelperSet()->has('question')) {
//            $inputStream = $this->getHelperSet()->get('question')->getInputStream();
//            if (!@posix_isatty($inputStream)) {
//                $input->setInteractive(false);
//            }
        }

        if (true === $input->hasParameterOption(array('--quiet', '-q'))) {
            $output->setVerbosity(OutputInterface::VERBOSITY_QUIET);
        } else {
            if ($input->hasParameterOption('-vvv') || $input->hasParameterOption('--verbose=3') || $input->getParameterOption('--verbose') === 3) {
                $output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
            } elseif ($input->hasParameterOption('-vv') || $input->hasParameterOption('--verbose=2') || $input->getParameterOption('--verbose') === 2) {
                $output->setVerbosity(OutputInterface::VERBOSITY_VERY_VERBOSE);
            } elseif ($input->hasParameterOption('-v') || $input->hasParameterOption('--verbose=1') || $input->hasParameterOption('--verbose') || $input->getParameterOption('--verbose')) {
                $output->setVerbosity(OutputInterface::VERBOSITY_VERBOSE);
            }
        }
    }

    public function getHelp()
    {
        return $this->getLongVersion();
    }

    public function getLongVersion()
    {
        if ('UNKNOWN' !== $this->getName() && 'UNKNOWN' !== $this->getVersion()) {
            return sprintf('<info>%s</info> version <comment>%s</comment>', $this->getName(), $this->getVersion());
        }
        return '<info>Console Tool</info>';
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getVersion()
    {
        return $this->version;
    }

    protected function getCommandName(InputInterface $input)
    {
        return $input->getFirstArgument();
    }

    private function extractAllNamespaces($name)
    {
        //则返回除了最后一个元素外的所有元素
        $parts = explode(':', $name, -1);
        $namespaces = array();
        foreach ($parts as $part) {
            if (count($namespaces)) {
                $namespaces[] = end($namespaces).':'.$part;
            } else {
                $namespaces[] = $part;
            }
        }
        return $namespaces;
    }

    public function all($namespace = null)
    {
        if (null === $namespace) {
            return $this->commands;
        }

        $commands = array();
        foreach ($this->commands as $name => $command) {
            if ($namespace === $this->extractNamespace($name, substr_count($namespace, ':') + 1)) {
                $commands[$name] = $command;
            }
        }

        return $commands;
    }

    public function extractNamespace($name, $limit = null)
    {
        $parts = explode(':', $name);
        array_pop($parts);
        return implode(':', null === $limit ? $parts : array_slice($parts, 0, $limit));
    }

    private function getAbbreviationSuggestions($abbrevs)
    {
        return sprintf('%s, %s%s', $abbrevs[0], $abbrevs[1], count($abbrevs) > 2 ? sprintf(' and %d more', count($abbrevs) - 2) : '');
    }

    protected function getDefaultCommands()
    {
        return array(new HelpCommand(), new ListCommand());
    }

    public function getSchedule() {
        static $schedule;
        if (is_null($schedule))
        {
            $schedule =  new Schedule;
        }
        return $schedule;
    }


    public function terminate() {

    }

    //todo
    public function call($callback, $param = []) {
        if(is_array($param)) {
            $param = [$param];
        }
        return call_user_func_array($callback, $param);
    }

}