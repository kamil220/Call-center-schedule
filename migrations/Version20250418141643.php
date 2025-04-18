<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250418141643 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE users CHANGE id id CHAR(36) NOT NULL, CHANGE manager_id manager_id CHAR(36) DEFAULT NULL, CHANGE employment_type employment_type VARCHAR(255) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE work_schedule_availabilities ADD time_range_start_time TIME NOT NULL, ADD time_range_end_time TIME NOT NULL, DROP start_time, DROP end_time, CHANGE user_id user_id CHAR(36) NOT NULL, CHANGE employment_type employment_type VARCHAR(255) NOT NULL, CHANGE date date DATE NOT NULL, CHANGE recurrence_pattern recurrence_pattern_data JSON DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE work_schedule_availabilities RENAME INDEX idx_availability_user TO IDX_92A80C4A76ED395
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE work_schedule_availabilities ADD start_time TIME NOT NULL COMMENT '(DC2Type:time_immutable)', ADD end_time TIME NOT NULL COMMENT '(DC2Type:time_immutable)', DROP time_range_start_time, DROP time_range_end_time, CHANGE employment_type employment_type VARCHAR(255) NOT NULL COMMENT '(DC2Type:AppDomainUserValueObjectEmploymentType)', CHANGE date date DATE NOT NULL COMMENT '(DC2Type:date_immutable)', CHANGE user_id user_id CHAR(36) NOT NULL COMMENT '(DC2Type:user_id)', CHANGE recurrence_pattern_data recurrence_pattern JSON DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE work_schedule_availabilities RENAME INDEX idx_92a80c4a76ed395 TO IDX_AVAILABILITY_USER
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE users CHANGE id id CHAR(36) NOT NULL COMMENT '(DC2Type:user_id)', CHANGE employment_type employment_type VARCHAR(255) NOT NULL COMMENT '(DC2Type:AppDomainUserValueObjectEmploymentType)', CHANGE manager_id manager_id CHAR(36) DEFAULT NULL COMMENT '(DC2Type:user_id)'
        SQL);
    }
}
