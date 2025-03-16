<?php

declare(strict_types=1);

namespace Neucore\Factory;

use Doctrine\Persistence\ObjectManager;
use Neucore\Entity\Alliance;
use Neucore\Entity\App;
use Neucore\Entity\AppRequests;
use Neucore\Entity\Character;
use Neucore\Entity\CharacterNameChange;
use Neucore\Entity\Corporation;
use Neucore\Entity\CorporationMember;
use Neucore\Entity\EsiLocation;
use Neucore\Entity\EsiToken;
use Neucore\Entity\EsiType;
use Neucore\Entity\EveLogin;
use Neucore\Entity\Group;
use Neucore\Entity\GroupApplication;
use Neucore\Entity\Player;
use Neucore\Entity\PlayerLogins;
use Neucore\Entity\RemovedCharacter;
use Neucore\Entity\Role;
use Neucore\Entity\Plugin;
use Neucore\Entity\SystemVariable;
use Neucore\Entity\Watchlist;
use Neucore\Repository\AllianceRepository;
use Neucore\Repository\AppRepository;
use Neucore\Repository\AppRequestsRepository;
use Neucore\Repository\CharacterNameChangeRepository;
use Neucore\Repository\CharacterRepository;
use Neucore\Repository\CorporationMemberRepository;
use Neucore\Repository\CorporationRepository;
use Neucore\Repository\EsiLocationRepository;
use Neucore\Repository\EsiTokenRepository;
use Neucore\Repository\EsiTypeRepository;
use Neucore\Repository\EveLoginRepository;
use Neucore\Repository\GroupApplicationRepository;
use Neucore\Repository\GroupRepository;
use Neucore\Repository\PlayerLoginsRepository;
use Neucore\Repository\PlayerRepository;
use Neucore\Repository\RemovedCharacterRepository;
use Neucore\Repository\RoleRepository;
use Neucore\Repository\PluginRepository;
use Neucore\Repository\SystemVariableRepository;
use Neucore\Repository\WatchlistRepository;

class RepositoryFactory
{
    private static ?self $instance = null;

    private ObjectManager $objectManager;

    private array $factories = [];

    public static function getInstance(ObjectManager $objectManager): self
    {
        if (self::$instance === null) {
            self::$instance = new self($objectManager);
        }
        return self::$instance;
    }

    public function __construct(ObjectManager $objectManager)
    {
        self::$instance = $this;
        $this->objectManager = $objectManager;
    }

    public function getAllianceRepository(): AllianceRepository
    {
        return $this->getRepository(AllianceRepository::class, Alliance::class);
    }

    public function getAppRepository(): AppRepository
    {
        return $this->getRepository(AppRepository::class, App::class);
    }

    public function getAppRequestsRepository(): AppRequestsRepository
    {
        return $this->getRepository(AppRequestsRepository::class, AppRequests::class);
    }

    public function getCharacterRepository(): CharacterRepository
    {
        return $this->getRepository(CharacterRepository::class, Character::class);
    }

    public function getCharacterNameChangeRepository(): CharacterNameChangeRepository
    {
        return $this->getRepository(CharacterNameChangeRepository::class, CharacterNameChange::class);
    }

    public function getCorporationRepository(): CorporationRepository
    {
        return $this->getRepository(CorporationRepository::class, Corporation::class);
    }

    public function getCorporationMemberRepository(): CorporationMemberRepository
    {
        return $this->getRepository(CorporationMemberRepository::class, CorporationMember::class);
    }

    public function getEsiLocationRepository(): EsiLocationRepository
    {
        return $this->getRepository(EsiLocationRepository::class, EsiLocation::class);
    }

    public function getEsiTokenRepository(): EsiTokenRepository
    {
        return $this->getRepository(EsiTokenRepository::class, EsiToken::class);
    }

    public function getEsiTypeRepository(): EsiTypeRepository
    {
        return $this->getRepository(EsiTypeRepository::class, EsiType::class);
    }

    public function getEveLoginRepository(): EveLoginRepository
    {
        return $this->getRepository(EveLoginRepository::class, EveLogin::class);
    }

    public function getGroupRepository(): GroupRepository
    {
        return $this->getRepository(GroupRepository::class, Group::class);
    }

    public function getGroupApplicationRepository(): GroupApplicationRepository
    {
        return $this->getRepository(GroupApplicationRepository::class, GroupApplication::class);
    }

    public function getPlayerRepository(): PlayerRepository
    {
        return $this->getRepository(PlayerRepository::class, Player::class);
    }

    public function getPlayerLoginsRepository(): PlayerLoginsRepository
    {
        return $this->getRepository(PlayerLoginsRepository::class, PlayerLogins::class);
    }

    public function getRoleRepository(): RoleRepository
    {
        return $this->getRepository(RoleRepository::class, Role::class);
    }

    public function getPluginRepository(): PluginRepository
    {
        return $this->getRepository(PluginRepository::class, Plugin::class);
    }

    public function getSystemVariableRepository(): SystemVariableRepository
    {
        return $this->getRepository(SystemVariableRepository::class, SystemVariable::class);
    }

    public function getRemovedCharacterRepository(): RemovedCharacterRepository
    {
        return $this->getRepository(RemovedCharacterRepository::class, RemovedCharacter::class);
    }

    public function getWatchlistRepository(): WatchlistRepository
    {
        return $this->getRepository(WatchlistRepository::class, Watchlist::class);
    }

    /**
     * @phpstan-param class-string<T> $entityClass
     * @template T of object
     */
    private function getRepository(string $repositoryClass, string $entityClass): mixed
    {
        if (!isset($this->factories[$repositoryClass])) {
            $metadata = $this->objectManager->getClassMetadata($entityClass);
            $repository = new $repositoryClass($this->objectManager, $metadata);
            $this->factories[$repositoryClass] = $repository;
        }
        return $this->factories[$repositoryClass];
    }
}
