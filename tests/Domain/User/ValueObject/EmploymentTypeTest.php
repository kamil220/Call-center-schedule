<?php

declare(strict_types=1);

namespace App\Tests\Domain\User\ValueObject;

use App\Domain\User\ValueObject\EmploymentType;
use PHPUnit\Framework\TestCase;

final class EmploymentTypeTest extends TestCase
{
    public function testEmploymentContractLimits(): void
    {
        $type = EmploymentType::EMPLOYMENT_CONTRACT;
        
        $this->assertEquals(8, $type->getMaxDailyHours());
        $this->assertEquals(40, $type->getMaxWeeklyHours());
        $this->assertEquals(72, $type->getMinimumNoticePeriodHours());
        $this->assertTrue($type->requiresRecurringSchedule());
    }

    public function testB2BContractLimits(): void
    {
        $type = EmploymentType::B2B_CONTRACT;
        
        $this->assertEquals(24, $type->getMaxDailyHours());
        $this->assertEquals(168, $type->getMaxWeeklyHours());
        $this->assertEquals(24, $type->getMinimumNoticePeriodHours());
        $this->assertFalse($type->requiresRecurringSchedule());
    }

    public function testCivilContractLimits(): void
    {
        $type = EmploymentType::CIVIL_CONTRACT;
        
        $this->assertEquals(12, $type->getMaxDailyHours());
        $this->assertEquals(60, $type->getMaxWeeklyHours());
        $this->assertEquals(48, $type->getMinimumNoticePeriodHours());
        $this->assertFalse($type->requiresRecurringSchedule());
    }
} 