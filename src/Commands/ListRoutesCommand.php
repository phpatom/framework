<?php


namespace Oxygen\Providers\Console\Commands\Routing;

use Atom\Contracts\Routing\RouterContract;
use Atom\Routing\Router;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListRoutesCommand extends Command
{
    protected static $defaultName = 'list:routes';
    /**
     * @var Router
     */
    private $router;

    public function __construct(RouterContract $router)
    {
        $this->router = $router;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setDescription('List all the routes of the application');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $table = new Table($output);
        $routes = $this->formattedRoutes();
        if (!empty($routes)) {
            $table->setHeaderTitle('Routes');
            $table->setHeaders(['Name', 'Methods', 'prefix','Path','Middleware']);
            $table->setRows($routes);
            $table->render();
            return 0;
        }
        $output->writeln("<error>No routes found</error>");
        return 0;
    }

    private function formattedRoutes():array
    {
        $rows = [];
        foreach ($this->router->getRoutes() as $route) {
            $methods = implode($route->getMethods(), ", ");
            $rows[] = [
                $route->getName(),
                $methods,
                '',
                $route->getPattern(),
                $route->getMiddleware()
            ];
        }
        foreach ($this->router->getRouteGroups() as $routeGroup) {
            $rows[] = new TableSeparator();
            foreach ($routeGroup->getRoutes() as $route) {
                $rows[] = [
                    $route->getName(),
                    implode($route->getMethods(), ", "),
                    $routeGroup->getPattern(),
                    $route->getPattern(),
                    $route->getMiddleware()
                ];
            }
        }
        return $rows;
    }
}
