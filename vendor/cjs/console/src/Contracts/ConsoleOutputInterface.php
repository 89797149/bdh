<?php
namespace CjsConsole\Contracts;

interface ConsoleOutputInterface extends OutputInterface
{

    public function getErrorOutput();

    public function setErrorOutput(OutputInterface $error);

}
