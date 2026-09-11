<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260911090839 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE booking (id INT AUTO_INCREMENT NOT NULL, post_ride_validation VARCHAR(20) NOT NULL, passenger_id INT NOT NULL, carpool_id INT NOT NULL, INDEX IDX_E00CEDDE4502E565 (passenger_id), INDEX IDX_E00CEDDE9A6F0DAE (carpool_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE brand (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE carpool (id INT AUTO_INCREMENT NOT NULL, departure_city VARCHAR(100) NOT NULL, departure_address VARCHAR(255) NOT NULL, arrival_city VARCHAR(100) NOT NULL, arrival_address VARCHAR(255) NOT NULL, departure_at DATETIME NOT NULL, arrival_at DATETIME NOT NULL, credit_cost_per_passenger INT NOT NULL, initial_seat_count SMALLINT NOT NULL, remaining_seat_count SMALLINT NOT NULL, status VARCHAR(30) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, driver_id INT NOT NULL, vehicle_id INT NOT NULL, INDEX IDX_E95D90CCC3423909 (driver_id), INDEX IDX_E95D90CC545317D1 (vehicle_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE credit_transaction (id INT AUTO_INCREMENT NOT NULL, amount INT NOT NULL, transaction_type VARCHAR(30) NOT NULL, description VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, carpool_id INT DEFAULT NULL, INDEX IDX_5E1DE3E1A76ED395 (user_id), INDEX IDX_5E1DE3E19A6F0DAE (carpool_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE review (id INT AUTO_INCREMENT NOT NULL, rating SMALLINT NOT NULL, comment LONGTEXT DEFAULT NULL, status VARCHAR(30) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, moderated_at DATETIME DEFAULT NULL, passenger_id INT NOT NULL, carpool_id INT NOT NULL, INDEX IDX_794381C64502E565 (passenger_id), INDEX IDX_794381C69A6F0DAE (carpool_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, username VARCHAR(50) NOT NULL, credits INT NOT NULL, is_driver TINYINT NOT NULL, is_passenger TINYINT NOT NULL, accepts_smokers TINYINT NOT NULL, accepts_pets TINYINT NOT NULL, custom_preferences LONGTEXT DEFAULT NULL, profile_picture VARCHAR(255) DEFAULT NULL, is_active TINYINT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE vehicle (id INT AUTO_INCREMENT NOT NULL, registration_number VARCHAR(20) NOT NULL, first_registration_date DATE NOT NULL, model VARCHAR(100) NOT NULL, color VARCHAR(50) DEFAULT NULL, energy_type VARCHAR(50) NOT NULL, seat_count SMALLINT NOT NULL, owner_id INT NOT NULL, INDEX IDX_1B80E4867E3C61F9 (owner_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE booking ADD CONSTRAINT FK_E00CEDDE4502E565 FOREIGN KEY (passenger_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE booking ADD CONSTRAINT FK_E00CEDDE9A6F0DAE FOREIGN KEY (carpool_id) REFERENCES carpool (id)');
        $this->addSql('ALTER TABLE carpool ADD CONSTRAINT FK_E95D90CCC3423909 FOREIGN KEY (driver_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE carpool ADD CONSTRAINT FK_E95D90CC545317D1 FOREIGN KEY (vehicle_id) REFERENCES vehicle (id)');
        $this->addSql('ALTER TABLE credit_transaction ADD CONSTRAINT FK_5E1DE3E1A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE credit_transaction ADD CONSTRAINT FK_5E1DE3E19A6F0DAE FOREIGN KEY (carpool_id) REFERENCES carpool (id)');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C64502E565 FOREIGN KEY (passenger_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT FK_794381C69A6F0DAE FOREIGN KEY (carpool_id) REFERENCES carpool (id)');
        $this->addSql('ALTER TABLE vehicle ADD CONSTRAINT FK_1B80E4867E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE booking DROP FOREIGN KEY FK_E00CEDDE4502E565');
        $this->addSql('ALTER TABLE booking DROP FOREIGN KEY FK_E00CEDDE9A6F0DAE');
        $this->addSql('ALTER TABLE carpool DROP FOREIGN KEY FK_E95D90CCC3423909');
        $this->addSql('ALTER TABLE carpool DROP FOREIGN KEY FK_E95D90CC545317D1');
        $this->addSql('ALTER TABLE credit_transaction DROP FOREIGN KEY FK_5E1DE3E1A76ED395');
        $this->addSql('ALTER TABLE credit_transaction DROP FOREIGN KEY FK_5E1DE3E19A6F0DAE');
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C64502E565');
        $this->addSql('ALTER TABLE review DROP FOREIGN KEY FK_794381C69A6F0DAE');
        $this->addSql('ALTER TABLE vehicle DROP FOREIGN KEY FK_1B80E4867E3C61F9');
        $this->addSql('DROP TABLE booking');
        $this->addSql('DROP TABLE brand');
        $this->addSql('DROP TABLE carpool');
        $this->addSql('DROP TABLE credit_transaction');
        $this->addSql('DROP TABLE review');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE vehicle');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
