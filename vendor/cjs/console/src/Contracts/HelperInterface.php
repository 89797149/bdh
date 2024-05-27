<?php
namespace CjsConsole\Contracts;

use CjsConsole\Helper\HelperSet;
interface HelperInterface
{
    public function setHelperSet(HelperSet $helperSet = null);

    public function getHelperSet();
    
    public function getName();
}
