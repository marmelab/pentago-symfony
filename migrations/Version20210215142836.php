<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210215142836 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX uniq_232b318c9fd0a00b');
        $this->addSql('ALTER TABLE game RENAME COLUMN current_player TO current_player_id');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318C42C04473 FOREIGN KEY (current_player_id) REFERENCES player (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_232B318C42C04473 ON game (current_player_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE game DROP CONSTRAINT FK_232B318C42C04473');
        $this->addSql('DROP INDEX IDX_232B318C42C04473');
        $this->addSql('ALTER TABLE game RENAME COLUMN current_player_id TO current_player');
        $this->addSql('CREATE UNIQUE INDEX uniq_232b318c9fd0a00b ON game (current_player)');
    }
}
