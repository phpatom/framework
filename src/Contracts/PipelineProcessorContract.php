<?php


namespace Atom\Framework\Contracts;

interface PipelineProcessorContract
{
    public function process($data, $handler, $pipeline);

    public function shouldStop($result);
}
