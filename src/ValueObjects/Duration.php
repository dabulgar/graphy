<?php

namespace Davos\Graphy\ValueObjects;

use Davos\Graphy\ValueObjects\Exceptions\DurationFormatException;

class Duration
{
    private string $duration;
    
    private int $durationInSeconds;
    
    public function __construct(string|int $rrdDuration)
    {
        if (!preg_match('/^(\d+)([smhdwMy])?$/', (string)$rrdDuration, $match)) {
            throw DurationFormatException::invalidDuration($rrdDuration);
        }
        
        $this->setDuration($rrdDuration);
        $this->setDurationInSeconds($match);
    }
    
    public function getDuration(): string
    {
        return $this->duration;
    }
    
    private function setDuration(string $duration): void
    {
        $this->duration = $duration;
    }
    
    public function getDurationInSeconds(): int
    {
        return $this->durationInSeconds;
    }
    
    private function setDurationInSeconds(array $match): void
    {
        $unit = $match[2] ?? 's';
        
        $number = (int)$match[1];
        
        switch ($unit) {
            case 's':
            default:
                $this->durationInSeconds = $number;
                break;
            case 'm':
                $this->durationInSeconds = ($number * 60);
                break;
            case 'h':
                $this->durationInSeconds = ($number * 3600);
                break;
            case 'd':
                $this->durationInSeconds = ($number * 86400);
                break;
            case 'w':
                $this->durationInSeconds = ($number * 604800);
                break;
            case 'M':
                $this->durationInSeconds = ($number * 2678400);
                break;
            case 'y':
                $this->durationInSeconds = ($number * 31622400);
                break;
        }
    }
}
