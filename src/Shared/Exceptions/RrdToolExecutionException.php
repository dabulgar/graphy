<?php

namespace Davos\Graphy\Shared\Exceptions;

class RrdToolExecutionException extends GraphyException
{
    public function __construct(
        protected string $rrdError,
        protected string $fileName,
        protected string $action,
        protected array  $options,
        protected int    $exitCode = 0,
        ?\Throwable      $previous = null,
    )
    {
        $message = sprintf(
            "RRDTool %s failed for '%s'%s%s",
            $this->action,
            $this->fileName,
            $this->formatError(),
            $this->buildCommandPreview()
        );

        parent::__construct($message, $this->exitCode, $previous);
    }

    protected function formatError(): string
    {
        if ($this->rrdError === '') {
            return '';
        }

        return sprintf(
            " | Error: %s",
            mb_strimwidth($this->rrdError, 0, 120, '...')
        );
    }

    protected function buildCommandPreview(): string
    {
        $options = implode(' ', $this->options);

        $command = " | Command: rrdtool {$this->action} {$this->fileName} {$options}";

        return mb_strimwidth($command, 0, 120, '...');
    }

    public function getRrdError(): string
    {
        return $this->rrdError;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}
