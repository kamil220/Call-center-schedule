<?php

declare(strict_types=1);

namespace App\Domain\WorkSchedule\Repository;

use App\Domain\User\Entity\User;
use App\Domain\WorkSchedule\Entity\Availability;
use DateTimeImmutable;
use Doctrine\Persistence\ObjectRepository;

interface AvailabilityRepositoryInterface extends ObjectRepository
{
    public function save(Availability $availability): void;
    
    public function remove(Availability $availability): void;
    
    public function findById(string $id): ?Availability;
    
    /**
     * @return Availability[]
     */
    public function findByUser(User $user): array;
    
    /**
     * @return Availability[]
     */
    public function findByUserAndDateRange(User $user, DateTimeImmutable $startDate, DateTimeImmutable $endDate): array;
    
    /**
     * @return array<Availability>
     */
    public function findOverlapping(Availability $availability): array;
} 