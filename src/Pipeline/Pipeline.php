<?php


namespace Atom\Framework\Http;

use Atom\Framework\Contracts\PipelineProcessorContract;

class Pipeline
{
    private array $pipes;
    private int $index = 0;
    private int $length;
    /**
     * @var mixed|null
     */
    protected $current;
    /**
     * @var mixed
     */
    private $data;
    /**
     * @var PipelineProcessorContract
     */
    private PipelineProcessorContract $processor;

    public function __construct($data, PipelineProcessorContract $processor, array $pipes = [])
    {
        $this->data = $data;
        $this->processor = $processor;
        $this->pipes = $pipes;
        $this->current = $this->data;
        $this->length = count($this->pipes);
    }

    public function length(): int
    {
        return $this->length;
    }

    public function next()
    {
        $handler = $this->currentPipe();
        if (is_null($handler)) {
            return null;
        }
        $this->index++;
        $this->current = $this->processor->process($this->current, $handler, $this);
        if ($this->processor->shouldStop($this->current)) {
            $this->index = $this->length;
            return $this->current;
        }
        return $this->current;
    }

    public function current()
    {
        return $this->current;
    }

    public function completed(): bool
    {
        return $this->index > ($this->length - 1);
    }

    public function run()
    {
        while (!$this->completed()) {
            $this->next();
        }
        return $this->current();
    }

    public static function send($data): PipelineFactory
    {
        return (new PipelineFactory())->pipe($data);
    }

    protected function currentPipe()
    {
        return $this->pipes[$this->index] ?? null;
    }
}
