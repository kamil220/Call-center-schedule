<?php

declare(strict_types=1);

namespace App\Domain\Forecast\ValueObject;

use App\Domain\Forecast\ValueObject\ForecastDemand;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class ForecastResult
{
    /** @var Collection<int, ForecastDemand> */
    private Collection $demands;

    public function __construct()
    {
        /** @var ArrayCollection<int, ForecastDemand> */
        $this->demands = new ArrayCollection();
    }

    public function addDemand(ForecastDemand $demand): self
    {
        if (!$this->demands->contains($demand)) {
            $this->demands->add($demand);
        }
        return $this;
    }

    public function getDemands(): Collection
    {
        return $this->demands;
    }

    public function getByDate(\DateTimeInterface $date): Collection
    {
        return $this->demands->filter(
            fn (ForecastDemand $demand) => $demand->getDate()->format('Y-m-d') === $date->format('Y-m-d')
        );
    }
} 