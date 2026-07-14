<?php

namespace Davos\Graphy\Builder;

final class DS
{
    private ?string $name = null;
    private ?string $type = null;
    private ?int $heartbeat = null;
    private null|string|int|float $min = 'U';
    private null|string|int|float $max = 'U';
    private ?string $expression = null;

    public static function name(string $name): self
    {
        return (new static())->setName($name);
    }

    private function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function gauge(): self
    {
        $this->type = 'GAUGE';

        return $this;
    }

    public function counter(): self
    {
        $this->type = 'COUNTER';

        return $this;
    }

    public function dcounter(): self
    {
        $this->type = 'DCOUNTER';

        return $this;
    }

    public function derive(): self
    {
        $this->type = 'DERIVE';

        return $this;
    }

    public function dderive(): self
    {
        $this->type = 'DDERIVE';

        return $this;
    }

    public function absolute(): self
    {
        $this->type = 'ABSOLUTE';

        return $this;
    }

    public function compute(string $expression): self
    {
        $this->type = 'COMPUTE';
        $this->expression = $expression;

        return $this;
    }

    public function heartbeat(int $heartbeat): self
    {
        $this->heartbeat = $heartbeat;

        return $this;
    }

    public function min(int|string|float $min): self
    {
        $this->min = $min;

        return $this;
    }

    public function max(int|string|float $max): self
    {
        $this->max = $max;

        return $this;
    }

    public function __toString(): string
    {
        if ($this->type === 'COMPUTE') {
            return sprintf(
                'DS:%s:%s:%s',
                $this->name ?? '',
                $this->type ?? '',
                $this->expression ?? ''
            );
        }

        return sprintf(
            'DS:%s:%s:%s:%s:%s',
            $this->name ?? '',
            $this->type ?? '',
            $this->heartbeat ?? '',
            $this->min ?? '',
            $this->max ?? ''
        );
    }
}