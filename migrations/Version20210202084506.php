<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210202084506 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE game (id UUID NOT NULL, player1_hash VARCHAR(255) DEFAULT NULL, player2_hash VARCHAR(255) DEFAULT NULL, board JSON NOT NULL, turn_status VARCHAR(255) NOT NULL, player_turn INT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN game.id IS \'(DC2Type:uuid)\'');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP TABLE game');
    }
}
