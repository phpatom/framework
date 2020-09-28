<?php

namespace Oxygen\Providers\Console\Commands\Modules;

use Atom\App\App;
use Atom\App\FileSystem\DiskManager;
use Atom\App\FileSystem\DiskNotFoundException;
use League\Flysystem\FileExistsException;
use League\Flysystem\FileNotFoundException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeModuleCommand extends Command
{
    /**
     * @var DiskManager
     */
    private $diskManager;
    /**
     * @var String
     */
    private $modulePath;

    public function __construct(DiskManager $diskManager, String $modulePath)
    {

        parent::__construct();
        $this->diskManager = $diskManager;
        $this->modulePath = $modulePath;
    }

    protected static $defaultName = "make:module";

    protected function configure()
    {
        $this->addArgument("module", InputArgument::REQUIRED, "Name of the module");
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws DiskNotFoundException
     * @throws FileExistsException
     * @throws FileNotFoundException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $localDisk = $this->diskManager->get("local");
        $moduleName = $input->getArgument("module");
        $modulePath = $this->modulePath;
        $modulePath.= DIRECTORY_SEPARATOR.ucfirst($moduleName);

        if ($localDisk->has($modulePath.DIRECTORY_SEPARATOR)) {
            $output->writeln("Module already exists on [$modulePath]");
            return 0;
        }
        $fileName = ucfirst($moduleName)."Module.php";
        $localDisk->write("$modulePath/$fileName", $this->getTemplate($moduleName));
        $localDisk->createDir("$modulePath/Controllers");
        $moduleClass = "\App\Modules\\".$moduleName."\\".ucfirst($moduleName)."Module::class";
        $mainMiddlewareContent = $localDisk->read("src".DIRECTORY_SEPARATOR."App/Main.php");
        $mainMiddlewareContent = str_replace(
            "[MODULES]",
            "[MODULES] \n\t\t\$handler->load($moduleClass);",
            $mainMiddlewareContent
        );
        $localDisk->put("src".DIRECTORY_SEPARATOR."App/Main.php", $mainMiddlewareContent);
        $output->writeln("Module successfully created on [$modulePath]");
        return 0;
    }

    private function getTemplate(string $moduleName)
    {
        $moduleName = ucfirst($moduleName);
        $moduleNameLowerCase = strtolower($moduleName);
        $moduleNameSpace = "App\Modules\\".$moduleName;
        $moduleClassname = $moduleName."Module";
        return <<<EOT
<?php
namespace $moduleNameSpace;

use Oxygen\AbstractTypes\AbstractModule;
use Oxygen\Providers\Routing\Route;
use Oxygen\Providers\Routing\Router;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Oxygen\Contracts\AppContract;

class $moduleClassname extends AbstractModule implements MiddlewareInterface
{
    public \$MODULE_NAME="$moduleName";
    public \$MODULE_DESCRIPTION ="$moduleName module";

    protected function addRoutes(Router \$router)
    {
        \$router->add(Route::get(
            "/$moduleNameLowerCase",
            "$moduleNameLowerCase.home",
            IndexController::class
        ));
    }

    protected function setUp(ServerRequestInterface \$request, AppContract \$app)
    {
    }
}
EOT;
    }
}
