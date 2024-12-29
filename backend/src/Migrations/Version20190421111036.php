<?php

declare(strict_types=1);

namespace Neucore\Migrations;

use Neucore\Entity\RemovedCharacter;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190421111036 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    /**
     * @throws Exception
     */
    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs

        $this->addSql(
            "UPDATE removed_characters SET reason = '" . RemovedCharacter::REASON_DELETED_MANUALLY . "' 
            WHERE reason = 'deleted (manually)'",
        );
        $this->addSql(
            "UPDATE removed_characters SET reason = '" . RemovedCharacter::REASON_DELETED_BIOMASSED . "' 
            WHERE reason = 'deleted (biomassed)'",
        );
        $this->addSql(
            "UPDATE removed_characters SET reason = '" . RemovedCharacter::REASON_DELETED_OWNER_CHANGED . "' 
            WHERE reason = 'deleted (EVE account changed)'",
        );

        $this->addSql('ALTER TABLE removed_characters CHANGE reason reason VARCHAR(32) NOT NULL');
    }

    /**
     * @throws Exception
     */
    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs

        $this->addSql('ALTER TABLE removed_characters CHANGE reason reason VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_520_ci`');

        $this->addSql(
            "UPDATE removed_characters SET reason = 'deleted (manually)' 
            WHERE reason = '" . RemovedCharacter::REASON_DELETED_MANUALLY . "'",
        );
        $this->addSql(
            "UPDATE removed_characters SET reason = 'deleted (biomassed)' 
            WHERE reason = '" . RemovedCharacter::REASON_DELETED_BIOMASSED . "'",
        );
        $this->addSql(
            "UPDATE removed_characters SET reason = 'deleted (EVE account changed)' 
            WHERE reason = '" . RemovedCharacter::REASON_DELETED_OWNER_CHANGED . "'",
        );
    }
}
