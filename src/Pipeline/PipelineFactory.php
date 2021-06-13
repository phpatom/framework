<?php


namespace Atom\Framework\Http;

use Atom\Framework\Contracts\PipelineProcessorContract;

class PipelineFactory
{
    protected array $pipes;
    /**
     * @var mixed
     */
    protected $data;

    protected PipelineProcessorContract $processor;

    /**
     * @param $data
     * @return PipelineFactory
     */
    public function pipe($data): PipelineFactory
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @param array $pipes
     * @return $this
     */
    public function through(array $pipes): PipelineFactory
    {
        $this->pipes = $pipes;
        return $this;
    }

    public function via(
        PipelineProcessorContract $processor
    ): PipelineFactory {
        $this->processor = $processor;
        return $this;
    }

    public function make(): Pipeline
    {
        return new Pipeline($this->data, $this->processor, $this->pipes);
    }

    public function run()
    {
        return $this->make()->run();
    }
}
