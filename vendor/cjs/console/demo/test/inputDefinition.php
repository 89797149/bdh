<?php
require_once dirname(__DIR__) . '/common.php';


use CjsConsole\Input\InputDefinition;
use CjsConsole\Input\InputArgument;
use CjsConsole\Input\InputOption;


$obj = new InputDefinition(array(
    new InputArgument('command', InputArgument::REQUIRED, 'The command to execute'),

    new InputOption('--help',           '-h', InputOption::VALUE_NONE, 'Display this help message'),
    new InputOption('--quiet',          '-q', InputOption::VALUE_NONE, 'Do not output any message'),
    new InputOption('--verbose', '-v|vv|vvv', InputOption::VALUE_NONE, 'Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug'),
    new InputOption('--version',        '-V', InputOption::VALUE_NONE, 'Display this application version'),
    new InputOption('--ansi',           '',   InputOption::VALUE_NONE, 'Force ANSI output'),
    new InputOption('--no-ansi',        '',   InputOption::VALUE_NONE, 'Disable ANSI output'),
    new InputOption('--no-interaction', '-n', InputOption::VALUE_NONE, 'Do not ask any interactive question'),
));


echo $obj->getOption('version')->getName() . PHP_EOL;
//[-h|--help] [-q|--quiet] [-v|vv|vvv|--verbose] [-V|--version] [--ansi] [--no-ansi] [-n|--no-interaction] command
echo $obj->getSynopsis() . PHP_EOL;



