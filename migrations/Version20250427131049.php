<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250427131049 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Convert DATETIME columns to DATE in work_schedule_leave_requests table';
    }

    public function up(Schema $schema): void
    {
        // Convert DATETIME columns to DATE while preserving data
        $this->addSql('ALTER TABLE work_schedule_leave_requests 
            MODIFY start_date DATE,
            MODIFY end_date DATE,
            MODIFY approval_date DATE,
            MODIFY created_at DATE,
            MODIFY updated_at DATE');
    }

    public function down(Schema $schema): void
    {
        // Convert DATE columns back to DATETIME
        $this->addSql('ALTER TABLE work_schedule_leave_requests 
            MODIFY start_date DATETIME,
            MODIFY end_date DATETIME,
            MODIFY approval_date DATETIME,
            MODIFY created_at DATETIME,
            MODIFY updated_at DATETIME');
    }
}
