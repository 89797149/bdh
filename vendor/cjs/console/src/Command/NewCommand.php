<?php
namespace CjsConsole\Command;

use ZipArchive;
use RuntimeException;

use CjsConsole\Input\InputArgument;
use CjsConsole\Input\InputOption;
use CjsConsole\Contracts\InputInterface;
use CjsConsole\Contracts\OutputInterface;
use CjsConsole\Command;
use CjsConsole\Process\Process;

class NewCommand extends Command
{

    protected function configure()
    {
        $this
            ->setName('new')
            ->setDescription('Create a new application.')
            ->addArgument('name', InputArgument::OPTIONAL)
            ->addOption('dev', null, InputOption::VALUE_NONE, 'Installs the latest "development" release');
    }

    /**
     * Execute the command.
     *
     * @param  \CjsConsole\Input\InputInterface  $input
     * @param  \CjsConsole\Output\OutputInterface  $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if (! class_exists('ZipArchive')) {
            throw new RuntimeException('The Zip PHP extension is not installed. Please install it and try again.');
        }
        //验证项目目录是否存在,存在则抛异常
        $this->verifyApplicationDoesntExist(
            $directory = ($input->getArgument('name')) ? getcwd().'/'.$input->getArgument('name') : getcwd()
        );

        $output->writeln('<info>Crafting application...</info>');
        $version = $this->getVersion($input);//需要哪个版本文件
        $this->download($zipFile = $this->makeFilename(), $version)
             ->extract($zipFile, $directory)
             ->cleanUp($zipFile);
        $composer = $this->findComposer();
        $commands = [
            $composer.' install --no-scripts',
            $composer.' run-script post-root-package-install',
            $composer.' run-script post-install-cmd',
            $composer.' run-script post-create-project-cmd',
        ];
        if ($input->getOption('no-ansi')) {
            $commands = array_map(function ($value) {
                                    return $value.' --no-ansi';
                                }, $commands);
        }
        /**
        composer install --no-scripts && composer run-script post-root-package-install && composer run-script post-install-cmd && composer run-script post-create-project-cmd
         */
        echo "todo shell cmd: " . implode(' && ', $commands) . PHP_EOL;
//        $process = new Process();
//        $process->setCmd(implode(' && ', $commands))->setCwd($directory);
//        if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
//            $process->setTty(true);
//        }
//        $process->run(function ($type, $line) use ($output) {
//            $output->write($line);
//        });
        $output->writeln('<comment>Application ready!</comment>');
    }

    /**
     * Verify that the application does not already exist.
     *
     * @param  string  $directory
     * @return void
     */
    protected function verifyApplicationDoesntExist($directory)
    {
        if ((is_dir($directory) || is_file($directory)) && $directory != getcwd()) {
            throw new RuntimeException('Application already exists!');
        }
    }

    /**
     * 在当前工作目录下/随机命名zip文件名
     * @return string
     */
    protected function makeFilename()
    {
        return getcwd().'/cjs_'.md5(time().uniqid()).'.zip';
    }
    /**
     * 下载zip文件
     * @param  string  $zipFile
     * @param  string  $version
     * @return $this
     */
    protected function download($zipFile, $version = 'master')
    {
        switch ($version) {
            case 'develop':
                $filename = 'latest-develop.zip';
                break;
            case 'master':
                $filename = 'latest.zip';
                break;
        }

        $contents = file_get_contents("http://cabinet.laravel.com/" . $filename);
        file_put_contents($zipFile, $contents);
        return $this;
    }

    /**
     * 解压zip文件到指定目录
     * @param  string  $zipFile
     * @param  string  $directory
     * @return $this
     */
    protected function extract($zipFile, $directory)
    {
        $archive = new ZipArchive;
        $archive->open($zipFile);
        $archive->extractTo($directory);
        $archive->close();
        return $this;
    }

    /**
     * 删除下载的zip文件
     * @param  string  $zipFile
     * @return $this
     */
    protected function cleanUp($zipFile)
    {
        @chmod($zipFile, 0777);
        @unlink($zipFile);
        return $this;
    }

    /**
     * 获取版本: 主分支master和开发分支develop
     * @return string
     */
    protected function getVersion(InputInterface $input)
    {
        if ($input->getOption('dev')) {
            return 'develop';
        }
        return 'master';
    }

    /**
     * 查找composer命令
     * @return string
     */
    protected function findComposer()
    {
        if (file_exists(getcwd().'/composer.phar')) {
            return '"'.PHP_BINARY.'" composer.phar';
        }
        return 'composer';
    }

}
