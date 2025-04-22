<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250421210942 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create work_schedule_leave_requests table for sick leave and holiday management';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE work_schedule_leave_requests (
            id CHAR(36) NOT NULL,
            user_id CHAR(36) NOT NULL,
            approver_id CHAR(36) DEFAULT NULL,
            type VARCHAR(30) NOT NULL,
            status VARCHAR(20) NOT NULL,
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            reason LONGTEXT DEFAULT NULL,
            approval_date DATETIME DEFAULT NULL,
            comments LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME DEFAULT NULL,
            INDEX IDX_LEAVE_USER (user_id),
            INDEX IDX_LEAVE_APPROVER (approver_id),
            INDEX IDX_LEAVE_STATUS (status),
            INDEX IDX_LEAVE_TYPE (type),
            INDEX IDX_LEAVE_DATES (start_date, end_date),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        
        $this->addSql('ALTER TABLE work_schedule_leave_requests ADD CONSTRAINT FK_LEAVE_USER_FK FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE work_schedule_leave_requests ADD CONSTRAINT FK_LEAVE_APPROVER_FK FOREIGN KEY (approver_id) REFERENCES users (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE work_schedule_leave_requests');
    }
} 