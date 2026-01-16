<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260116185938 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE client (idClient INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, PRIMARY KEY (idClient)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE commande (idCommande INT AUTO_INCREMENT NOT NULL, date_heure DATETIME NOT NULL, statut VARCHAR(255) NOT NULL, total DOUBLE PRECISION NOT NULL, idClient INT NOT NULL, INDEX IDX_6EEAA67DA455ACCF (idClient), PRIMARY KEY (idCommande)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE ligne_commande (idLigneCommande INT AUTO_INCREMENT NOT NULL, quantite INT NOT NULL, idCommande INT NOT NULL, idPlat INT NOT NULL, INDEX IDX_3170B74B3D498C26 (idCommande), INDEX IDX_3170B74B53C5FC99 (idPlat), PRIMARY KEY (idLigneCommande)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE plat (idPlat INT AUTO_INCREMENT NOT NULL, nom_plat VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, prix DOUBLE PRECISION NOT NULL, PRIMARY KEY (idPlat)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DA455ACCF FOREIGN KEY (idClient) REFERENCES client (idClient)');
        $this->addSql('ALTER TABLE ligne_commande ADD CONSTRAINT FK_3170B74B3D498C26 FOREIGN KEY (idCommande) REFERENCES commande (idCommande)');
        $this->addSql('ALTER TABLE ligne_commande ADD CONSTRAINT FK_3170B74B53C5FC99 FOREIGN KEY (idPlat) REFERENCES plat (idPlat)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DA455ACCF');
        $this->addSql('ALTER TABLE ligne_commande DROP FOREIGN KEY FK_3170B74B3D498C26');
        $this->addSql('ALTER TABLE ligne_commande DROP FOREIGN KEY FK_3170B74B53C5FC99');
        $this->addSql('DROP TABLE client');
        $this->addSql('DROP TABLE commande');
        $this->addSql('DROP TABLE ligne_commande');
        $this->addSql('DROP TABLE plat');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
