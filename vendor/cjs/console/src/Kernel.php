<?php
namespace CjsConsole;

use CjsConsole\Scheduling\Schedule;
use Exception;

class Kernel {

    protected $commands =[];

    protected $consoleApp;

    public function __construct(ConsoleApp $consoleApp)
    {
        $this->consoleApp = $consoleApp;

        $this->defineConsoleSchedule();
    }

    //设置时间表
    protected function defineConsoleSchedule()
    {
        $schedule = $this->consoleApp->getSchedule();

        $this->schedule($schedule);
    }

    //时间计划表
    protected function schedule(Schedule $schedule)
    {
        //
    }

    /**
     * 返回状态码
     *
     * @param $input
     * @param null $output
     * @return int
     */
    public function handle($input, $output = null)
    {
        try {
            return $this->consoleApp->setCommandConfig($this->commands)->run($input, $output);
        } catch (Exception $e) {

            return 1;
        }
    }



    public function terminate($input, $status) {

        $this->consoleApp->terminate();
    }

}