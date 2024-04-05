<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240404140223 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE label (id INT AUTO_INCREMENT NOT NULL, id_label VARCHAR(90) NOT NULL, name VARCHAR(45) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE label_has_artist (id INT AUTO_INCREMENT NOT NULL, left_at DATETIME NOT NULL, joined_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE label_has_artist_label (label_has_artist_id INT NOT NULL, label_id INT NOT NULL, INDEX IDX_289FEF8F5207E184 (label_has_artist_id), INDEX IDX_289FEF8F33B92F39 (label_id), PRIMARY KEY(label_has_artist_id, label_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE label_has_artist_artist (label_has_artist_id INT NOT NULL, artist_id INT NOT NULL, INDEX IDX_D2A95AC35207E184 (label_has_artist_id), INDEX IDX_D2A95AC3B7970CF8 (artist_id), PRIMARY KEY(label_has_artist_id, artist_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE label_has_artist_label ADD CONSTRAINT FK_289FEF8F5207E184 FOREIGN KEY (label_has_artist_id) REFERENCES label_has_artist (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE label_has_artist_label ADD CONSTRAINT FK_289FEF8F33B92F39 FOREIGN KEY (label_id) REFERENCES label (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE label_has_artist_artist ADD CONSTRAINT FK_D2A95AC35207E184 FOREIGN KEY (label_has_artist_id) REFERENCES label_has_artist (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE label_has_artist_artist ADD CONSTRAINT FK_D2A95AC3B7970CF8 FOREIGN KEY (artist_id) REFERENCES artist (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE label_has_artist_label DROP FOREIGN KEY FK_289FEF8F5207E184');
        $this->addSql('ALTER TABLE label_has_artist_label DROP FOREIGN KEY FK_289FEF8F33B92F39');
        $this->addSql('ALTER TABLE label_has_artist_artist DROP FOREIGN KEY FK_D2A95AC35207E184');
        $this->addSql('ALTER TABLE label_has_artist_artist DROP FOREIGN KEY FK_D2A95AC3B7970CF8');
        $this->addSql('DROP TABLE label');
        $this->addSql('DROP TABLE label_has_artist');
        $this->addSql('DROP TABLE label_has_artist_label');
        $this->addSql('DROP TABLE label_has_artist_artist');
    }
}
