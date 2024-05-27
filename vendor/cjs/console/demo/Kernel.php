<?php
namespace ConsoleDemo;

use CjsConsole\Scheduling\Schedule;
use CjsConsole\Kernel as ConsoleKernel;

//file: App/Console/Kernel.php
class Kernel extends ConsoleKernel {

    //配置命令
    protected $commands = [
                            'ConsoleDemo\Commands\Inspire',
                            ];


    //时间计划表配置  * * * * * php /path/to/artisan schedule:run 1>> /dev/null 2>&1
    protected function schedule(Schedule $schedule)
    {
        //$schedule->command('inspire')->hourly();

        $schedule->command('command:contract_timeout')->dailyAt('00:00');

        $schedule->command('report:finance yesterday')->dailyAt('03:00');

		$schedule->command('command:paymentaccountcheck')->cron('50 * * * *');
        $schedule->command('command:consume_order_coupon')->hourly();
        $schedule->command('aaz:withdrawjob')->everyTenMinutes();

        $schedule->exec('ls -l')->cron('* * * * *')->then(function(){
                echo "ls -l 执行完毕" . PHP_EOL;
        });

        //设置只在指定环境运行
        $schedule->command('inspire')->cron('* * * * *')->environments('dev');

    }


}