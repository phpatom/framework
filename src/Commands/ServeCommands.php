<?php
namespace Oxygen\Providers\Console\Commands;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ServeCommands extends Command
{
    protected static $defaultName = 'serve';
    private $publicPath;

    public function __construct(string $publicPath)
    {
        $this->publicPath =  $publicPath;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setDescription('Serve the application');
        $this->addOption(
            "port",
            "p",
            InputOption::VALUE_OPTIONAL,
            "The port where you want to serve",
            "8000"
        );
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $port = $input->getOption("port");
        try {
            $publicPath = $this->publicPath;
            $version = $this->getApplication()->getVersion();
            $output->writeln("Atom CLI v$version");
            $output->writeln(">> App serve on port $port");
            exec("php -S localhost:$port -t $publicPath");
        } catch (Exception $e) {
            $output->writeln($e->getMessage()."\n ".$e->getTraceAsString());
        }
        return 0;
    }
}
