<?php
namespace CjsConsole\Input;


class InputOption
{
    const VALUE_NONE = 1;   //标记不接收值
    const VALUE_REQUIRED = 2; //标记值必须
    const VALUE_OPTIONAL = 4; //标记值可选
    const VALUE_IS_ARRAY = 8;  //标记值为数组

    private $name;  //选项名  --help
    private $shortcut; //选项短名  -h,多个用|分割,如 '-v|vv|vvv'
    private $mode;  //值是VALUE_*常量的值之一, 1-15
    private $default;  //选项默认值
    private $description; //选项描述

    public function __construct($name, $shortcut = null, $mode = null, $description = '', $default = null)
    {
        if (0 === strpos($name, '--')) {
            $name = substr($name, 2);
        }

        if (empty($name)) {
            throw new \InvalidArgumentException('An option name cannot be empty.');
        }

        if (empty($shortcut)) {
            $shortcut = null;
        }

        if (null !== $shortcut) {
            if (is_array($shortcut)) {
                $shortcut = implode('|', $shortcut);
            }
            $shortcuts = preg_split('{(\|)-?}', ltrim($shortcut, '-'));
            $shortcuts = array_filter($shortcuts);
            $shortcut = implode('|', $shortcuts);

            if (empty($shortcut)) {
                throw new \InvalidArgumentException('An option shortcut cannot be empty.');
            }
        }

        if (null === $mode) {
            $mode = self::VALUE_NONE;
        } elseif (!is_int($mode) || $mode > 15 || $mode < 1) {
            throw new \InvalidArgumentException(sprintf('Option mode "%s" is not valid.', $mode));
        }

        $this->name = $name;
        $this->shortcut = $shortcut;
        $this->mode = $mode;
        $this->description = $description;

        if ($this->isArray() && !$this->acceptValue()) {//选项值接收为数组 且不是(必须 或者 可选 之一)就抛异常
            throw new \InvalidArgumentException('Impossible to have an option mode VALUE_IS_ARRAY if the option does not accept a value.');
        }

        $this->setDefault($default);
    }

    public function getShortcut()
    {
        return $this->shortcut;
    }

    public function getName()
    {
        return $this->name;
    }

    //值是必须 或者 VALUE_OPTIONAL 之一 就为真
    public function acceptValue()
    {
        return $this->isValueRequired() || $this->isValueOptional();
    }

    /**
     * 选项是否必须
     */
    public function isValueRequired()
    {
        return self::VALUE_REQUIRED === (self::VALUE_REQUIRED & $this->mode);
    }

    public function isValueOptional()
    {
        return self::VALUE_OPTIONAL === (self::VALUE_OPTIONAL & $this->mode);
    }

    public function isArray()
    {
        return self::VALUE_IS_ARRAY === (self::VALUE_IS_ARRAY & $this->mode);
    }

    public function setDefault($default = null)
    {
        if (self::VALUE_NONE === (self::VALUE_NONE & $this->mode) && null !== $default) {
            throw new \LogicException('Cannot set a default value when using InputOption::VALUE_NONE mode.');
        }

        if ($this->isArray()) {
            if (null === $default) {
                $default = array();
            } elseif (!is_array($default)) {
                throw new \LogicException('A default value for an array option must be an array.');
            }
        }

        $this->default = $this->acceptValue() ? $default : false;
    }

    public function getDefault()
    {
        return $this->default;
    }

    public function getDescription()
    {
        return $this->description;
    }
    //2个选项对象比较是否一样
    public function equals(InputOption $option)
    {
        return $option->getName() === $this->getName()
        && $option->getShortcut() === $this->getShortcut()
        && $option->getDefault() === $this->getDefault()
        && $option->isArray() === $this->isArray()
        && $option->isValueRequired() === $this->isValueRequired()
        && $option->isValueOptional() === $this->isValueOptional()
            ;
    }

}
