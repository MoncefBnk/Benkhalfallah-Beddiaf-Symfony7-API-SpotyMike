<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240419113620 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE featuring (id INT AUTO_INCREMENT NOT NULL, id_song_id INT NOT NULL, id_featuring VARCHAR(90) NOT NULL, INDEX IDX_73A30F0C7E201B83 (id_song_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE featuring_artist (featuring_id INT NOT NULL, artist_id INT NOT NULL, INDEX IDX_80914391A71CD2BA (featuring_id), INDEX IDX_80914391B7970CF8 (artist_id), PRIMARY KEY(featuring_id, artist_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE featuring ADD CONSTRAINT FK_73A30F0C7E201B83 FOREIGN KEY (id_song_id) REFERENCES song (id)');
        $this->addSql('ALTER TABLE featuring_artist ADD CONSTRAINT FK_80914391A71CD2BA FOREIGN KEY (featuring_id) REFERENCES featuring (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE featuring_artist ADD CONSTRAINT FK_80914391B7970CF8 FOREIGN KEY (artist_id) REFERENCES artist (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE song_artist DROP FOREIGN KEY FK_722870DA0BDB2F3');
        $this->addSql('ALTER TABLE song_artist DROP FOREIGN KEY FK_722870DB7970CF8');
        $this->addSql('DROP TABLE song_artist');
        $this->addSql('ALTER TABLE artist ADD followers LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE song_artist (song_id INT NOT NULL, artist_id INT NOT NULL, INDEX IDX_722870DB7970CF8 (artist_id), INDEX IDX_722870DA0BDB2F3 (song_id), PRIMARY KEY(song_id, artist_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE song_artist ADD CONSTRAINT FK_722870DA0BDB2F3 FOREIGN KEY (song_id) REFERENCES song (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE song_artist ADD CONSTRAINT FK_722870DB7970CF8 FOREIGN KEY (artist_id) REFERENCES artist (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE featuring DROP FOREIGN KEY FK_73A30F0C7E201B83');
        $this->addSql('ALTER TABLE featuring_artist DROP FOREIGN KEY FK_80914391A71CD2BA');
        $this->addSql('ALTER TABLE featuring_artist DROP FOREIGN KEY FK_80914391B7970CF8');
        $this->addSql('DROP TABLE featuring');
        $this->addSql('DROP TABLE featuring_artist');
        $this->addSql('ALTER TABLE artist DROP followers');
    }
}
