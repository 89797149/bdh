<?php
namespace CjsConsole\Contracts;

interface DescriptorInterface
{
    
    public function describe(OutputInterface $output, $object, array $options = array());
}
