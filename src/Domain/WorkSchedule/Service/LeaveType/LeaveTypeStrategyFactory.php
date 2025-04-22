<?php

declare(strict_types=1);

namespace App\Domain\WorkSchedule\Service\LeaveType;

class LeaveTypeStrategyFactory
{
    /**
     * @var LeaveTypeStrategyInterface[]
     */
    private array $strategies = [];
    
    /**
     * @param iterable<LeaveTypeStrategyInterface> $strategies
     */
    public function __construct(iterable $strategies)
    {
        foreach ($strategies as $strategy) {
            $this->strategies[$strategy->getType()] = $strategy;
        }
    }
    
    /**
     * Get a strategy by leave type
     */
    public function getStrategy(string $type): LeaveTypeStrategyInterface
    {
        if (!isset($this->strategies[$type])) {
            throw new \InvalidArgumentException(sprintf('No leave type strategy found for type "%s"', $type));
        }
        
        return $this->strategies[$type];
    }
    
    /**
     * Check if a strategy exists for the given type
     */
    public function hasStrategy(string $type): bool
    {
        return isset($this->strategies[$type]);
    }
    
    /**
     * @return LeaveTypeStrategyInterface[]
     */
    public function getAllStrategies(): array
    {
        return $this->strategies;
    }
    
    /**
     * Get all available leave types as an array for forms etc.
     * 
     * @return array<string, string> Array of type => label
     */
    public function getLeaveTypeChoices(): array
    {
        $choices = [];
        foreach ($this->strategies as $type => $strategy) {
            $choices[$strategy->getLabel()] = $type;
        }
        
        return $choices;
    }
} 