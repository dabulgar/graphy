<?php

namespace Davos\Graphy\Builder;

class RRA
{
    private ?string $cf = null;
    private float $xff = 0.5;
    private ?int $steps = null;
    private ?int $rows = null;

    public static function average(): self
    {
        return (new static())->setCf('AVERAGE');
    }

    public static function min(): self
    {
        return (new static())->setCf('MIN');
    }

    public static function max(): self
    {
        return (new static())->setCf('MAX');
    }

    public static function last(): self
    {
        return (new static())->setCf('LAST');
    }

    private function setCf(string $cf): self
    {
        $this->cf = $cf;

        return $this;
    }

    public function xff(float $xff): self
    {
        $this->xff = $xff;

        return $this;
    }

    public function steps(int $steps): self
    {
        $this->steps = $steps;

        return $this;
    }

    public function rows(int $rows): self
    {
        $this->rows = $rows;

        return $this;
    }

    public function __toString(): string
    {
        return sprintf(
            "RRA:%s:%s:%s:%s",
            $this->cf ?? '',
            $this->xff,
            $this->steps ?? '',
            $this->rows ?? '',
        );
    }
}