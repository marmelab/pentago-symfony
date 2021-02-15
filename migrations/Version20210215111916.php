<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210215111916 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE game ADD player1_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE game ADD player2_id UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE game ADD current_player UUID DEFAULT NULL');
        $this->addSql('ALTER TABLE game DROP player1_hash');
        $this->addSql('ALTER TABLE game DROP player2_hash');
        $this->addSql('ALTER TABLE game DROP current_player_hash');
        $this->addSql('COMMENT ON COLUMN game.player1_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN game.player2_id IS \'(DC2Type:uuid)\'');
        $this->addSql('COMMENT ON COLUMN game.current_player IS \'(DC2Type:uuid)\'');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318CC0990423 FOREIGN KEY (player1_id) REFERENCES player (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318CD22CABCD FOREIGN KEY (player2_id) REFERENCES player (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_232B318C9FD0A00B ON game (current_player)');
        $this->addSql('CREATE INDEX IDX_232B318CC0990423 ON game (player1_id)');
        $this->addSql('CREATE INDEX IDX_232B318CD22CABCD ON game (player2_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE game DROP CONSTRAINT FK_232B318CC0990423');
        $this->addSql('ALTER TABLE game DROP CONSTRAINT FK_232B318CD22CABCD');
        $this->addSql('DROP INDEX UNIQ_232B318C9FD0A00B');
        $this->addSql('DROP INDEX IDX_232B318CC0990423');
        $this->addSql('DROP INDEX IDX_232B318CD22CABCD');
        $this->addSql('ALTER TABLE game ADD player1_hash VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE game ADD player2_hash VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE game ADD current_player_hash VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE game DROP player1_id');
        $this->addSql('ALTER TABLE game DROP player2_id');
        $this->addSql('ALTER TABLE game DROP current_player');
    }
}
