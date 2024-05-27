<?php namespace CjsConsole\Command;


use CjsConsole\Input\InputOption;
use CjsConsole\Input\InputArgument;
use CjsConsole\Command;

/**
 * php artisan make:console AssignUsers --command=users:assign
 */
class MakeConsoleCommand extends Command {

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:console';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Create a new Artisan command";

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Console command';

    protected $appPath = '/tmp';
    protected $appNamespace = 'App\\'; //app命名空间


    public function __construct($name = null)
    {
        parent::__construct($name);
        $this->setDescription($this->description);
        //$this->ignoreValidationErrors();
    }


    protected function configure()
    {
        //方式2: 定义接收的选项和参数
//        $this->setDefinition(array(
//                                new InputOption('stime', null, InputOption::VALUE_REQUIRED, '时间间隔'),
//                            ));
    }


    public function setAppPath($appPath) {
        $this->appPath = $appPath;
        return $this;
    }

    public function setAppNamespace($appNamespace) {
        $this->appNamespace = $appNamespace;
        return $this;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        try{
            $name = $this->argument('name')?:'';
        } catch(\Exception $e) {
            echo $e->getMessage() . PHP_EOL;
        }

        $name = $this->parseName($name);//类的命名空间


        if (file_exists($fileName = $this->getPath($name)))
        {
            return $this->error($this->type.' already exists!');
        }

        $this->makeDirectory($fileName);

        file_put_contents($fileName, $this->buildClass($name));

        $this->info($this->type.' created successfully.');
    }

    protected function parseName($name)
    {
        $rootNamespace = $this->appNamespace;

        if (\CjsConsole\starts_with($name, $rootNamespace))
        {
            return $name;
        }

        if (\CjsConsole\str_contains($name, '/'))
        {
            $name = str_replace('/', '\\', $name);
        }

        return $this->parseName(trim($rootNamespace, '\\').'\Console\Commands\\'.$name);
    }

    protected function getPath($name)
    {
        $name = str_replace($this->appNamespace, '', $name);

        return $this->appPath.'/'.str_replace('\\', '/', $name).'.php';
    }

    protected function makeDirectory($path)
    {
        if ( !is_dir(dirname($path)))
        {
            @mkdir(dirname($path), 0777, true);
        }
    }

    protected function buildClass($name)
    {
        $stub = file_get_contents($this->getStub());
        $stub = str_replace(
                            '{{namespace}}',
                            $this->getNamespace($name),
                            $stub
                        );
        return $this->replaceClass($stub, $name);
    }

    /**
     * Replace the class name for the given stub.
     *
     * @param  string  $stub
     * @param  string  $name
     * @return string
     */
    protected function replaceClass($stub, $name)
    {
        $class = str_replace($this->getNamespace($name).'\\', '', $name);
        $stub = str_replace('{{class}}', $class, $stub);
        return str_replace('{{command}}', $this->option('command'), $stub);
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/stubs/console.stub';
    }

    protected function getNamespace($name)
    {
        return trim(implode('\\', array_slice(explode('\\', $name), 0, -1)), '\\');
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Console\Commands';
    }

    /**
     * Get the console command arguments.
     * 配置参数
     * @return array
     */
    protected function getArguments()
    {
        return array(
            // /usr/bin/php artisan make:console FooCommand
            array('name', InputArgument::REQUIRED, 'The name of the command.')
        );
    }

    /**
     * Get the console command options.
     * 配置选项
     * @return array
     */
    protected function getOptions()
    {
        return array(
            //--command=make:xxx
            array('command', null, InputOption::VALUE_OPTIONAL, 'The terminal command that should be assigned.', 'command:name'),
        );
    }


}
