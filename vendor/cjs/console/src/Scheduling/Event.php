<?php
namespace CjsConsole\Scheduling;

use Closure;
use CjsConsole\Process\Process;
use CjsConsole\Carbon;
use CjsCron\CronExpression;
use CjsConsole\ConsoleConfig;

class Event{

    protected $command;

    /**
     * cron表达式
     */
    protected $expression = '* * * * * *';

    protected $timezone;

    /**
     * linux用户
     */
    protected $user;

    /**
     * 环境列表
     */
    protected $environments = [];

    //是否在维护模式下也运行,默认否
    protected $evenInMaintenanceMode = false;

    /**
     * The filter callback.
     * @var \Closure
     */
    protected $filter;

    /**
     * The reject callback.
     * @var \Closure
     */
    protected $reject;

    /**
     * 输出到哪里
     * @var string
     */
    protected $output = '/dev/null';

    /**
     * @var array
     */
    protected $afterCallbacks = [];

    /**
     * @var string
     */
    protected $description;

    //执行命令对象
    protected $processObject = null;


    protected $curlObj = null;

    /**
     * @param  string  $command 命令
     * @return void
     */
    public function __construct($command)
    {
        $this->command = $command;
    }

    public function setProcessObject($process) {
        $this->processObject = $process;
        return $this;
    }

    public function getProcessObject() {
        return $this->processObject?: new Process();
    }

    /**
     * @return null
     */
    public function getCurlObj()
    {
        return $this->curlObj;
    }

    /**
     * @param null $curlObj
     */
    public function setCurlObj($curlObj)
    {
        $this->curlObj = $curlObj;
        return $this;
    }



    /**
     *
     * @param $container app对象,console app object
     * @return void
     */
    public function run($container = null)
    {
        if (count($this->afterCallbacks) > 0)
        {
            $this->runCommandInForeground($container);
        } else {
            $this->runCommandInBackground();
        }
    }

    /**
     * @return void
     */
    protected function runCommandInBackground()
    {
        if(\CjsConsole\base_path()){
            chdir(\CjsConsole\base_path());
        }

        exec($this->buildCommand());
    }

    /**
     * @param  $container  app对象
     * @return void
     */
    protected function runCommandInForeground($container=null)
    {
        $this->getProcessObject()->setCmd(trim($this->buildCommand(), '& '))->setCwd(\CjsConsole\base_path())->run();

        $this->callAfterCallbacks($container);
    }

    /**
     * Call all of the "after" callbacks for the event.
     *
     * @param  $container
     * @return void
     */
    protected function callAfterCallbacks($container)
    {
        foreach ($this->afterCallbacks as $callback)
        {
            if(is_object($container)) {
                $container->call($callback);
            }
        }
    }

    /**
     * @return string
     */
    public function buildCommand()
    {
        $command = $this->command.' > '.$this->output.' 2>&1 &';
        return $this->user ? 'sudo -u '.$this->user.' '.$command : $command;
    }

    /**
     * @param $app
     * @return bool
     */
    public function isDue($app = null)
    {
        if ( ! $this->runsInMaintenanceMode() && \CjsConsole\isDownForMaintenance())
        {
            return false;
        }
        //return $this->expressionPasses();
        return $this->expressionPasses() &&
                $this->filtersPass($app) &&
                $this->runsInEnvironment(ConsoleConfig::getInstance()->getEnvironments());
    }

    /**
     * Determine if the Cron expression passes.
     *
     * @return bool
     */
    protected function expressionPasses()
    {
        $date = Carbon::now();

        if ($this->timezone)
        {
            $date->setTimezone($this->timezone);
        }

        return CronExpression::factory($this->expression)->isDue($date);
    }

    /**
     * Determine if the filters pass for the event.
     *
     * @param $app = console app 对象
     * @return bool
     */
    protected function filtersPass($app)
    {

        if (($this->filter && ! $app->call($this->filter)) ||
            $this->reject && $app->call($this->reject))
        {
            return false;
        }

        return true;
    }

    /**
     * Determine if the event runs in the given environment.
     *
     * @param  string  $environment
     * @return bool
     */
    public function runsInEnvironment($environment)
    {
        return empty($this->environments) || in_array($environment, $this->environments);
    }

    /**
     * 在维护模式是否运行,true表示仍然运行,false表示不运行
     * @return bool
     */
    public function runsInMaintenanceMode()
    {
        return $this->evenInMaintenanceMode;
    }

    /**
     * The Cron expression representing the event's frequency.
     *
     * @param  string  $expression
     * @return $this
     */
    public function cron($expression)
    {
        $this->expression = $expression;
        return $this;
    }

    /**
     * Schedule the event to run hourly.
     * 每小时执行一次
     * @return $this
     */
    public function hourly()
    {
        return $this->cron('0 * * * * *');
    }

    /**
     * Schedule the event to run daily.
     * 每天执行
     * @return $this
     */
    public function daily()
    {
        return $this->cron('0 0 * * * *');
    }

    /**
     * Schedule the command at a given time.
     *
     * @param  string  $time
     * @return $this
     */
    public function at($time)
    {
        return $this->dailyAt($time);
    }

    /**
     * Schedule the event to run daily at a given time (10:00, 19:30, etc 时:分 格式).
     *
     * @param  string  $time
     * @return $this
     */
    public function dailyAt($time)
    {
        $segments = explode(':', $time);

        return $this->spliceIntoPosition(2, (int) $segments[0])
            ->spliceIntoPosition(1, count($segments) == 2 ? (int) $segments[1] : '0');
    }

    /**
     * Schedule the event to run twice daily.
     *
     * @return $this
     */
    public function twiceDaily()
    {
        return $this->cron('0 1,13 * * * *');
    }

    /**
     * Schedule the event to run only on weekdays.
     *
     * @return $this
     */
    public function weekdays()
    {
        return $this->spliceIntoPosition(5, '1-5');
    }

    /**
     * Schedule the event to run only on Mondays.
     *
     * @return $this
     */
    public function mondays()
    {
        return $this->days(1);
    }

    /**
     * Schedule the event to run only on Tuesdays.
     *
     * @return $this
     */
    public function tuesdays()
    {
        return $this->days(2);
    }

    /**
     * Schedule the event to run only on Wednesdays.
     *
     * @return $this
     */
    public function wednesdays()
    {
        return $this->days(3);
    }

    /**
     * Schedule the event to run only on Thursdays.
     *
     * @return $this
     */
    public function thursdays()
    {
        return $this->days(4);
    }

    /**
     * Schedule the event to run only on Fridays.
     *
     * @return $this
     */
    public function fridays()
    {
        return $this->days(5);
    }

    /**
     * Schedule the event to run only on Saturdays.
     *
     * @return $this
     */
    public function saturdays()
    {
        return $this->days(6);
    }

    /**
     * Schedule the event to run only on Sundays.
     *
     * @return $this
     */
    public function sundays()
    {
        return $this->days(0);
    }

    /**
     * Schedule the event to run weekly.
     *
     * @return $this
     */
    public function weekly()
    {
        return $this->cron('0 0 * * 0 *');
    }

    /**
     * Schedule the event to run weekly on a given day and time.
     *
     * @param  int  $day
     * @param  string  $time
     * @return $this
     */
    public function weeklyOn($day, $time = '0:0')
    {
        $this->dailyAt($time);

        return $this->spliceIntoPosition(5, $day);
    }

    /**
     * Schedule the event to run monthly.
     *
     * @return $this
     */
    public function monthly()
    {
        return $this->cron('0 0 1 * * *');
    }

    /**
     * Schedule the event to run yearly.
     *
     * @return $this
     */
    public function yearly()
    {
        return $this->cron('0 0 1 1 * *');
    }

    /**
     * Schedule the event to run every five minutes.
     *
     * @return $this
     */
    public function everyFiveMinutes()
    {
        return $this->cron('*/5 * * * * *');
    }

    /**
     * Schedule the event to run every ten minutes.
     *
     * @return $this
     */
    public function everyTenMinutes()
    {
        return $this->cron('*/10 * * * * *');
    }

    /**
     * Schedule the event to run every thirty minutes.
     *
     * @return $this
     */
    public function everyThirtyMinutes()
    {
        return $this->cron('0,30 * * * * *');
    }

    /**
     * Set the days of the week the command should run on.
     *
     * @param  array|dynamic  $days
     * @return $this
     */
    public function days($days)
    {
        $days = is_array($days) ? $days : func_get_args();
        return $this->spliceIntoPosition(5, implode(',', $days));
    }

    /**
     * Set the timezone the date should be evaluated on.
     *
     * @param  \DateTimeZone|string  $timezone
     * @return $this
     */
    public function timezone($timezone)
    {
        $this->timezone = $timezone;
        return $this;
    }

    /**
     * Set which user the command should run as.
     *
     * @param  string  $user
     * @return $this
     */
    public function user($user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * Limit the environments the command should run in.
     *
     * @param  array|dynamic  $environments
     * @return $this
     */
    public function environments($environments)
    {
        $this->environments = is_array($environments) ? $environments : func_get_args();
        return $this;
    }

    /**
     * 设置在维护模式也运行
     * @return $this
     */
    public function evenInMaintenanceMode()
    {
        $this->evenInMaintenanceMode = true;
        return $this;
    }

    /**
     * Register a callback to further filter the schedule.
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function when(Closure $callback)
    {
        $this->filter = $callback;
        return $this;
    }

    /**
     * Register a callback to further filter the schedule.
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function skip(Closure $callback)
    {
        $this->reject = $callback;
        return $this;
    }

    /**
     * Send the output of the command to a given location.
     *
     * @param  string  $location
     * @return $this
     */
    public function sendOutputTo($location)
    {
        $this->output = $location;
        return $this;
    }


    /**
     * Get the e-mail subject line for output results.
     *
     * @return string
     */
    protected function getEmailSubject()
    {
        if ($this->description)
        {
            return 'Scheduled Job Output ('.$this->description.')';
        }

        return 'Scheduled Job Output';
    }

    /**
     * 在预定工作执行之后 Ping 一个给定的 URL
     * $schedule->command('foo')->thenPing($url);
     * @param  string  $url
     * @return $this
     */
    public function thenPing($url)
    {
        return $this->then(function() use ($url) { $this->getCurlObj()->get($url); });
    }

    /**
     * Register a callback to be called after the operation.
     *
     * @param  \Closure  $callback
     * @return $this
     */
    public function then(Closure $callback)
    {
        $this->afterCallbacks[] = $callback;
        return $this;
    }

    /**
     * 设置描述
     * @param  string  $description
     * @return $this
     */
    public function description($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @param  int  $position
     * @param  string  $value
     * @return void
     */
    protected function spliceIntoPosition($position, $value)
    {
        $segments = explode(' ', $this->expression);
        $segments[$position - 1] = $value;
        return $this->cron(implode(' ', $segments));
    }

    /**
     * @return string
     */
    public function getSummaryForDisplay()
    {
        if (is_string($this->description)) return $this->description;
        return $this->buildCommand();
    }

    /**
     * Cron表达式
     * @return string
     */
    public function getExpression()
    {
        return $this->expression;
    }

}