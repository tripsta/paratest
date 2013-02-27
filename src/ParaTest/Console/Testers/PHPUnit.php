<?php namespace ParaTest\Console\Testers;

use Symfony\Component\Console\Command\Command,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Input\InputArgument,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface,
    ParaTest\Runners\PHPUnit\Runner;

class PHPUnit extends Tester
{
    protected $command;

    public function configure(Command $command)
    {
        $command
            ->addOption('phpunit', null, InputOption::VALUE_REQUIRED, 'The PHPUnit binary to execute. <comment>(default: vendor/bin/phpunit)</comment>')
            ->addOption('bootstrap', null, InputOption::VALUE_REQUIRED, 'The bootstrap file to be used by PHPUnit.')
            ->addOption('configuration', 'c', InputOption::VALUE_REQUIRED, 'The PHPUnit configuration file to use.')
            ->addOption('group', 'g', InputOption::VALUE_REQUIRED, 'Only runs tests from the specified group(s).')
            ->addOption('log-junit', null, InputOption::VALUE_REQUIRED, 'Log test execution in JUnit XML format to file.')
            ->addArgument('path', InputArgument::OPTIONAL, 'The path to a directory or file containing tests. <comment>(default: current directory)</comment>')
            ->addOption('path', null, InputOption::VALUE_REQUIRED, 'An alias for the path argument.');
        $this->command = $command;
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        if(!$this->hasConfig($input) && !$this->hasPath($input))
            $this->displayHelp($input, $output);
        $runner = new Runner($this->getRunnerOptions($input));
        $runner->run();
        return $runner->getExitCode();
    }

    protected function hasPath(InputInterface $input)
    {
        $argument = $input->getArgument('path');
        $option = $input->getOption('path');
        return $argument || $option;
    }

    protected function hasConfig(InputInterface $input)
    {
		return $this->getConfigFile($input) != null;
    }

	public function getConfigFile(InputInterface $input)
	{
        $cwd = getcwd() . DIRECTORY_SEPARATOR;

        $file = $input->getOption('configuration');
		if (!$file)
			$file = file_exists($cwd . 'phpunit.xml.dist') ? $cwd . 'phpunit.xml.dist' : null;
		if (!$file)
			$file = file_exists($cwd . 'phpunit.xml') ? $cwd . 'phpunit.xml' : null;
		return $file;
	}

	public function getBootstrapFromConfigFile($input)
	{
		$file = $this->getConfigFile($input);
		$configuration = \PHPUnit_Util_Configuration::getInstance($file);
		$configArray = $configuration->getPHPUnitConfiguration();
		if (file_exists(realpath($configArray['bootstrap']))) {
			return realpath($configArray['bootstrap']);
		}
		return null;
	}

    protected function getRunnerOptions(InputInterface $input)
    {
        $path = $input->getArgument('path');
        $options = $this->getOptions($input);
        if(isset($options['bootstrap']) && file_exists($options['bootstrap']))
            require_once $options['bootstrap'];
		else if ($this->getBootstrapFromConfigFile($input))
            require_once $this->getBootstrapFromConfigFile($input);
        $options = ($path) ? array_merge(array('path' => $path), $options) : $options;
        return $options;
    }
}