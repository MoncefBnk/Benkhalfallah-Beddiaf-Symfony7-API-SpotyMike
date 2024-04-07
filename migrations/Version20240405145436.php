<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240405145436 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE album (id INT AUTO_INCREMENT NOT NULL, artist_user_id_user_id INT DEFAULT NULL, id_album VARCHAR(90) NOT NULL, nom VARCHAR(95) NOT NULL, categ VARCHAR(20) NOT NULL, cover VARCHAR(125) NOT NULL, year INT NOT NULL, create_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', update_at DATETIME NOT NULL, INDEX IDX_39986E437E9F183A (artist_user_id_user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE artist (id INT AUTO_INCREMENT NOT NULL, user_id_user_id INT NOT NULL, label_has_artist_id INT NOT NULL, fullname VARCHAR(90) NOT NULL, label VARCHAR(90) NOT NULL, description LONGTEXT DEFAULT NULL, UNIQUE INDEX UNIQ_1599687DE94BC09 (user_id_user_id), INDEX IDX_15996875207E184 (label_has_artist_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE label (id INT AUTO_INCREMENT NOT NULL, label_has_artist_id INT DEFAULT NULL, id_label VARCHAR(90) NOT NULL, name VARCHAR(45) NOT NULL, INDEX IDX_EA750E85207E184 (label_has_artist_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE label_has_artist (id INT AUTO_INCREMENT NOT NULL, joined_at DATETIME NOT NULL, left_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE playlist (id INT AUTO_INCREMENT NOT NULL, playlist_has_song_id INT DEFAULT NULL, id_playlist VARCHAR(90) NOT NULL, title VARCHAR(50) NOT NULL, public TINYINT(1) NOT NULL, create_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', update_at DATETIME NOT NULL, INDEX IDX_D782112DE2815C07 (playlist_has_song_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE playlist_has_song (id INT AUTO_INCREMENT NOT NULL, download TINYINT(1) DEFAULT NULL, position SMALLINT DEFAULT NULL, create_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE song (id INT AUTO_INCREMENT NOT NULL, album_id INT DEFAULT NULL, playlist_has_song_id INT DEFAULT NULL, id_song VARCHAR(90) NOT NULL, title VARCHAR(255) NOT NULL, stream VARCHAR(125) NOT NULL, cover VARCHAR(125) NOT NULL, visibility TINYINT(1) NOT NULL, create_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_33EDEEA11137ABCF (album_id), INDEX IDX_33EDEEA1E2815C07 (playlist_has_song_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE song_artist (song_id INT NOT NULL, artist_id INT NOT NULL, INDEX IDX_722870DA0BDB2F3 (song_id), INDEX IDX_722870DB7970CF8 (artist_id), PRIMARY KEY(song_id, artist_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, id_user VARCHAR(90) NOT NULL, firstname VARCHAR(55) NOT NULL, lastname VARCHAR(55) NOT NULL, email VARCHAR(80) NOT NULL, tel VARCHAR(15) DEFAULT NULL, encrypte VARCHAR(90) NOT NULL, sexe VARCHAR(55) DEFAULT NULL, date_birth DATETIME NOT NULL, create_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', update_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE album ADD CONSTRAINT FK_39986E437E9F183A FOREIGN KEY (artist_user_id_user_id) REFERENCES artist (id)');
        $this->addSql('ALTER TABLE artist ADD CONSTRAINT FK_1599687DE94BC09 FOREIGN KEY (user_id_user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE artist ADD CONSTRAINT FK_15996875207E184 FOREIGN KEY (label_has_artist_id) REFERENCES label_has_artist (id)');
        $this->addSql('ALTER TABLE label ADD CONSTRAINT FK_EA750E85207E184 FOREIGN KEY (label_has_artist_id) REFERENCES label_has_artist (id)');
        $this->addSql('ALTER TABLE playlist ADD CONSTRAINT FK_D782112DE2815C07 FOREIGN KEY (playlist_has_song_id) REFERENCES playlist_has_song (id)');
        $this->addSql('ALTER TABLE song ADD CONSTRAINT FK_33EDEEA11137ABCF FOREIGN KEY (album_id) REFERENCES album (id)');
        $this->addSql('ALTER TABLE song ADD CONSTRAINT FK_33EDEEA1E2815C07 FOREIGN KEY (playlist_has_song_id) REFERENCES playlist_has_song (id)');
        $this->addSql('ALTER TABLE song_artist ADD CONSTRAINT FK_722870DA0BDB2F3 FOREIGN KEY (song_id) REFERENCES song (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE song_artist ADD CONSTRAINT FK_722870DB7970CF8 FOREIGN KEY (artist_id) REFERENCES artist (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE album DROP FOREIGN KEY FK_39986E437E9F183A');
        $this->addSql('ALTER TABLE artist DROP FOREIGN KEY FK_1599687DE94BC09');
        $this->addSql('ALTER TABLE artist DROP FOREIGN KEY FK_15996875207E184');
        $this->addSql('ALTER TABLE label DROP FOREIGN KEY FK_EA750E85207E184');
        $this->addSql('ALTER TABLE playlist DROP FOREIGN KEY FK_D782112DE2815C07');
        $this->addSql('ALTER TABLE song DROP FOREIGN KEY FK_33EDEEA11137ABCF');
        $this->addSql('ALTER TABLE song DROP FOREIGN KEY FK_33EDEEA1E2815C07');
        $this->addSql('ALTER TABLE song_artist DROP FOREIGN KEY FK_722870DA0BDB2F3');
        $this->addSql('ALTER TABLE song_artist DROP FOREIGN KEY FK_722870DB7970CF8');
        $this->addSql('DROP TABLE album');
        $this->addSql('DROP TABLE artist');
        $this->addSql('DROP TABLE label');
        $this->addSql('DROP TABLE label_has_artist');
        $this->addSql('DROP TABLE playlist');
        $this->addSql('DROP TABLE playlist_has_song');
        $this->addSql('DROP TABLE song');
        $this->addSql('DROP TABLE song_artist');
        $this->addSql('DROP TABLE user');
    }
}
