<?php

/** @noinspection PhpUnused */
/** @noinspection PhpIllegalPsrClassPathInspection */

declare(strict_types=1);

namespace Tests\Functional\Controller\User\ServiceController;

use Neucore\Plugin\Core\FactoryInterface;
use Neucore\Plugin\Data\CoreAccount;
use Neucore\Plugin\Data\CoreCharacter;
use Neucore\Plugin\Data\PluginConfiguration;
use Neucore\Plugin\Data\ServiceAccountData;
use Neucore\Plugin\Exception;
use Neucore\Plugin\ServiceInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

class TestService4 implements ServiceInterface
{
    public function __construct(
        LoggerInterface $logger,
        PluginConfiguration $pluginConfiguration,
        FactoryInterface $factory,
    ) {}

    public function onConfigurationChange(): void {}

    public function request(
        string $name,
        ServerRequestInterface $request,
        ResponseInterface $response,
        ?CoreAccount $coreAccount,
    ): ResponseInterface {
        throw new Exception();
    }

    public function getAccounts(array $characters): array
    {
        return [
            new ServiceAccountData(1),
        ];
    }

    public function register(
        CoreCharacter $character,
        array $groups,
        string $emailAddress,
        array $allCharacterIds,
    ): ServiceAccountData {
        throw new Exception();
    }

    public function updateAccount(CoreCharacter $character, array $groups, ?CoreCharacter $mainCharacter): void {}

    public function updatePlayerAccount(CoreCharacter $mainCharacter, array $groups): void {}

    public function moveServiceAccount(int $toPlayerId, int $fromPlayerId): bool
    {
        throw new Exception();
    }

    public function resetPassword(int $characterId): string
    {
        throw new Exception();
    }

    public function getAllAccounts(): array
    {
        throw new Exception();
    }

    public function getAllPlayerAccounts(): array
    {
        throw new Exception();
    }

    public function search(string $query): array
    {
        throw new Exception();
    }
}
