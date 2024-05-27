<?php
namespace CjsConsole\Scheduling;

use InvalidArgumentException;
class CallbackEvent extends Event {

    protected $callback;
    protected $parameters;

    public function __construct($callback, array $parameters = array())
    {
        $this->callback = $callback;
        $this->parameters = $parameters;

        if ( ! is_string($this->callback) && ! is_callable($this->callback))
        {
            throw new InvalidArgumentException(
                "Invalid scheduled callback event. Must be string or callable."
            );
        }
    }

    /**
     * @param  $container
     * @return mixed
     */
    public function run($container)
    {
        $response = $container->call($this->callback, $this->parameters);

        parent::callAfterCallbacks($container);

        return $response;
    }

    /**
     * @return string
     */
    public function getSummaryForDisplay()
    {
        if (is_string($this->description)) return $this->description;

        return is_string($this->callback) ? $this->callback : 'Closure';
    }

}