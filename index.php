<?php

use Atom\Framework\Contracts\PipelineProcessorContract;
use Atom\Framework\Http\Pipeline;
use Atom\Framework\Http\PipelineFactory;

require_once "vendor/autoload.php";
ini_set('display_errors', 1);

$fac = new PipelineFactory();
function add(int $i, int $j)
{
    echo "ajout de $i sur $j \n";
    return $i + $j;
}

function multiply(int $i, int $j)
{
    echo "multiplication de $i fois $j \n";
    return $i * $j;
}

class FunctionProcessor implements PipelineProcessorContract
{

    public function process($data, $handler, $pipeline)
    {
        return $handler($data, $pipeline);
    }

    public function shouldStop($res): bool
    {
        return $res == 22;
    }
}

$pipeline = Pipeline::send(2)->through([
    fn($i) => add($i, 2),
    function ($i, $h) {
        $res = $h->run() * 2;
        echo "fin\n";
        return $res;
    },
    function ($i, $h) {
        $res = $h->run() * 2;
        echo "fin 2\n";
        return $res;
    },
    fn($i) => add($i, 10),
    fn($i) => multiply($i, 2),

])->via(new FunctionProcessor())->make();
var_dump($pipeline->run());
