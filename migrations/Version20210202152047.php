<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210202152047 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE game ADD player_turn INT NOT NULL');
        $this->addSql('ALTER TABLE game DROP status');
        $this->addSql('ALTER TABLE game DROP current_player_hash');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE game ADD status VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE game ADD current_player_hash VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE game DROP player_turn');
    }
}
