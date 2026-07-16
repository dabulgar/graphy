<?php

namespace Davos\Graphy\ValueObjects;

class Flag
{
    private string $flag;

    /**
     * Flags can take string or integer values.
     * Boolean flags are used when no explicit value is required (e.g., --no-overwrite in rrdcreate).
     *
     * @var string|int|bool
     */
    private string|int|bool $value;

    public function __construct(string $flag, string|int|bool $value = true)
    {
        $this->flag = $flag;

        $this->value = $value;
    }

    public function getFlag(): string
    {
        return $this->flag;
    }

    public function getValue(): string|int|bool
    {
        return $this->value;
    }
}
