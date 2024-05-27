<?php
namespace CjsConsole\Scheduling;

use CjsConsole\Command;

class ScheduleRunCommand extends Command {

	/**
	 * The console command name.
	 * 其实就是php artisan 后的第1个参数名  如cli执行: php artisan schedule:run
	 * @var string
	 */
	protected $name = 'schedule:run';

	/**
	 * The console command description.
	 * @var string
	 */
	protected $description = 'Run the scheduled commands';

	/**
	 * The schedule instance.
	 *
	 * @var \CjsConsole\Scheduling\Schedule
	 */
	protected $schedule;

	/**
	 * Create a new command instance.
	 * @param  \CjsConsole\Scheduling\Schedule  $schedule
	 * @return void
	 */
	public function __construct(Schedule $schedule)
	{
		$this->schedule = $schedule;
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		$app = $this->getApplication();
		$events = $this->schedule->dueEvents($app);

		foreach ($events as $event)
		{
			$this->line('<info>Running scheduled command:</info> ' . $event->getSummaryForDisplay());
			//执行命令
			$event->run($app);
		}

		if (count($events) === 0)
		{
			$this->info('No scheduled commands are ready to run.');
		}
	}

}
