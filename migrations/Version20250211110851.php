<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250211110851 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE reservation_book (reservation_id INT NOT NULL, book_id INT NOT NULL, INDEX IDX_DDDC6E59B83297E7 (reservation_id), INDEX IDX_DDDC6E5916A2B381 (book_id), PRIMARY KEY(reservation_id, book_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE reservation_book ADD CONSTRAINT FK_DDDC6E59B83297E7 FOREIGN KEY (reservation_id) REFERENCES reservation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reservation_book ADD CONSTRAINT FK_DDDC6E5916A2B381 FOREIGN KEY (book_id) REFERENCES book (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE reservation_book DROP FOREIGN KEY FK_DDDC6E59B83297E7');
        $this->addSql('ALTER TABLE reservation_book DROP FOREIGN KEY FK_DDDC6E5916A2B381');
        $this->addSql('DROP TABLE reservation_book');
    }
}
