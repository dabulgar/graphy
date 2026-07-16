<?php

namespace Davos\Graphy\ValueObjects;

use Davos\Graphy\ValueObjects\Exceptions\DataSourceDefinitionException;

class DataSource
{
    public const VALID_TYPES = [
        'GAUGE',
        'COUNTER',
        'DERIVE',
        'DCOUNTER',
        'DDERIVE',
        'ABSOLUTE',
        'COMPUTE',
    ];
    
    private string $definition;
    private string $name;
    private string $type;
    private ?Duration $heartbeat = null;
    private ?string $min = null;
    private ?string $max = null;
    private ?string $expression = null;
    
    public function __construct(string $definition)
    {
        $this->definition = trim($definition);
        
        $arr = explode(':', $this->definition);
        if (in_array($arr[0] ?? null, ['DS', ''], true)) {
            array_shift($arr);
        }
        
        $this->setName($arr[0] ?? '');
        $this->setType($arr[1] ?? '');
        if ($this->getType() === 'COMPUTE') {
            $this->setExpression($arr[2] ?? '');
            return;
        }
        $this->setHeartbeat($arr[2] ?? '');
        $this->setMin($arr[3] ?? '');
        $this->setMax($arr[4] ?? '');
    }
    
    public function getName(): string
    {
        return $this->name;
    }
    
    private function setName(string $name): void
    {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
            throw DataSourceDefinitionException::invalidName($this->definition);
        }
        
        $this->name = $name;
    }
    
    public function getType(): string
    {
        return $this->type;
    }
    
    private function setType(string $type): void
    {
        if (!in_array($type, self::VALID_TYPES, true)) {
            throw DataSourceDefinitionException::invalidType($this->definition, self::VALID_TYPES);
        }
        
        $this->type = $type;
    }
    
    public function getHeartbeat(): ?int
    {
        return $this->heartbeat?->getDurationInSeconds();
    }
    
    private function setHeartbeat(string $heartbeat): void
    {
        $this->heartbeat = new Duration($heartbeat);
    }
    
    public function getMin(): ?string
    {
        return $this->min;
    }
    
    private function setMin(string $min): void
    {
        if ($this->isInvalidMinMaxValue($min)) {
            throw DataSourceDefinitionException::invalidMinValue($this->definition);
        }
        
        $this->min = $min;
    }
    
    public function getMax(): ?string
    {
        return $this->max;
    }
    
    private function setMax(string $max): void
    {
        if ($this->isInvalidMinMaxValue($max)) {
            throw DataSourceDefinitionException::invalidMaxValue($this->definition);
        }
        
        $this->max = $max;
    }
    
    public function getExpression(): ?string
    {
        return $this->expression;
    }
    
    private function setExpression(?string $expression): void
    {
        if ($expression === '') {
            throw DataSourceDefinitionException::invalidExpression($this->definition);
        }
        
        $this->expression = $expression;
    }
    
    private function isInvalidMinMaxValue(string $value): bool
    {
        return !is_numeric($value) && $value !== 'U';
    }
    
    public function getDefinition(): string
    {
        if ($this->getType() === 'COMPUTE') {
            return sprintf('DS:%s:%s:%s', $this->getName(), $this->getType(), $this->getExpression());
        }
        
        return sprintf(
            'DS:%s:%s:%s:%s:%s',
            $this->getName(), $this->getType(), $this->getHeartbeat(), $this->getMin(), $this->getMax()
        );
    }
}
