<?php

/** @noinspection DuplicatedCode */

declare(strict_types=1);

namespace Tests\Unit\Entity;

use Neucore\Entity\Alliance;
use Neucore\Entity\App;
use Neucore\Entity\Character;
use Neucore\Entity\CharacterNameChange;
use Neucore\Entity\Corporation;
use Neucore\Entity\EsiToken;
use Neucore\Entity\EveLogin;
use Neucore\Entity\Group;
use Neucore\Entity\GroupApplication;
use Neucore\Entity\Player;
use Neucore\Entity\RemovedCharacter;
use Neucore\Entity\Role;
use Neucore\Plugin\Data\CoreCharacter;
use Neucore\Plugin\Data\CoreGroup;
use Neucore\Plugin\Data\CoreRole;
use PHPUnit\Framework\TestCase;

class PlayerTest extends TestCase
{
    public function testJsonSerialize()
    {
        $a1 = (new App())->setName('app-one');
        $g1 = (new Group())->setName('gName');
        $g2 = (new Group())->setName('group2');
        $play = new Player();
        $play->setName('test user');
        $play->addGroup($g2);
        $play->addRole((new Role(1))->setName('rName'));
        $play->addRole((new Role(2))->setName('role2'));
        $c1 = new Character();
        $c2 = new Character();
        $c1->setId(123);
        $c2->setId(234);
        $c1->setMain(true);
        $c2->setMain(false);
        $c1->setName('eve one');
        $c2->setName('eve two');
        $c1->setCorporation((new Corporation())->setName('corp1')->setTicker('ABC')
            ->setAlliance((new Alliance())->setName('alli1')->setTicker('DEF')));
        $c1->addCharacterNameChange((new CharacterNameChange())->setOldName('old name'));
        $c1->addEsiToken((new EsiToken())->setEveLogin((new EveLogin())->setId(1)));
        $play->addCharacter($c1);
        $play->addCharacter($c2);
        $play->addManagerGroup($g1);
        $play->addManagerApp($a1);

        $expected1 = [
            'id' => null,
            'name' => 'test user',
            'status' => Player::STATUS_STANDARD,
            'roles' => ['rName', 'role2'],
            'characters' => [[
                'id' => 123,
                'name' => 'eve one',
                'main' => true,
                'created' => null,
                'lastUpdate' => null,
                'validToken' => null,
                'validTokenTime' => null,
                'tokenLastChecked' => null,
                'corporation' => [
                    'id' => 0,
                    'name' => 'corp1',
                    'ticker' => 'ABC',
                    'alliance' => ['id' => 0, 'name' => 'alli1', 'ticker' => 'DEF'],
                ],
                #'characterNameChanges' => [],
            ], [
                'id' => 234,
                'name' => 'eve two',
                'main' => false,
                'created' => null,
                'lastUpdate' => null,
                'validToken' => null,
                'validTokenTime' => null,
                'tokenLastChecked' => null,
                'corporation' => null,
                #'characterNameChanges' => [],
            ]],
            'groups' => [[
                'id' => null,
                'name' => 'group2',
                'description' => null,
                'visibility' => Group::VISIBILITY_PRIVATE,
                'autoAccept' => false,
                'isDefault' => false,
            ]],
            'managerGroups' => [[
                'id' => null,
                'name' => 'gName',
                'description' => null,
                'visibility' => Group::VISIBILITY_PRIVATE,
                'autoAccept' => false,
                'isDefault' => false,
            ]],
            'managerApps' => [['id' => null, 'name' => 'app-one', 'groups' => [], 'roles' => [], 'eveLogins' => []]],
        ];
        $this->assertSame($expected1, json_decode((string) json_encode($play), true));

        $this->assertSame(['id' => null, 'name' => 'test user'], $play->jsonSerialize(true));

        $expected2 = $expected1;
        $expected2['characters'][0]['characterNameChanges'] = [['oldName' => 'old name', 'changeDate' => null]];
        $expected2['characters'][1]['characterNameChanges'] = [];
        $this->assertSame($expected2, json_decode((string) json_encode($play->jsonSerialize(false, true)), true));

        $expected3 = $expected1;
        $expected3['characters'][0]['esiTokens'] = [[
            'eveLoginId' => 1,
            'characterId' => 0,
            'playerId' => 0,
            'validToken' => null,
            'validTokenTime' => null,
            'hasRoles' => null,
            'lastChecked' => null,
        ]];
        $expected3['characters'][1]['esiTokens'] = [];
        $this->assertSame(
            $expected3,
            json_decode((string) json_encode($play->jsonSerialize(false, false, true)), true),
        );
    }

    public function testToString()
    {
        $this->assertSame('Player Name #100', (new Player())->setId(100)->setName('Player Name')->__toString());
    }

    public function testSetGetId()
    {
        $this->assertSame(0, (new Player())->getId());
        $this->assertSame(5, (new Player())->setId(5)->getId());
    }

    public function testSetGetName()
    {
        $play = new Player();
        $play->setName('nam');
        $this->assertSame('nam', $play->getName());
    }

    public function testSetGetPassword()
    {
        $play = new Player();
        $play->setPassword('123456');
        $this->assertSame('123456', $play->getPassword());
    }

    /**
     * @throws \Exception
     */
    public function testSetGetLastUpdate()
    {
        $dt1 = new \DateTime('2018-04-26 18:59:36');

        $player = new Player();
        $player->setLastUpdate($dt1);
        $dt2 = $player->getLastUpdate();

        $this->assertNotSame($dt1, $dt2);
        $this->assertSame('2018-04-26T18:59:36+00:00', $dt2->format(\DateTimeInterface::ATOM));
    }

    public function testSetGetStatus()
    {
        $player = new Player();
        $this->assertSame(Player::STATUS_STANDARD, $player->getStatus());

        $player->setStatus(Player::STATUS_MANAGED);
        $this->assertSame(Player::STATUS_MANAGED, $player->getStatus());
    }

    public function testAddGetRemoveRole()
    {
        $player = new Player();
        $r1 = new Role(1);
        $r2 = new Role(2);
        $r1->setName('n1');
        $r2->setName('n2');

        $this->assertSame([], $player->getRoles());

        $player->addRole($r1);
        $player->addRole($r2);
        $this->assertSame([$r1, $r2], $player->getRoles());

        $player->removeRole($r2);
        $this->assertSame([$r1], $player->getRoles());
    }

    public function testGetCoreRoles()
    {
        $player = new Player();
        $r1 = (new Role(1))->setName('n1');
        $r2 = (new Role(2))->setName('n2');
        $player->addRole($r1)->addRole($r2);

        $this->assertSame(2, count($player->getCoreRoles()));
        $this->assertInstanceOf(CoreRole::class, $player->getCoreRoles()[0]);
        $this->assertInstanceOf(CoreRole::class, $player->getCoreRoles()[1]);
        $this->assertSame('n1', $player->getCoreRoles()[0]->name);
        $this->assertSame('n2', $player->getCoreRoles()[1]->name);
    }

    public function testGetRoleNames()
    {
        $player = new Player();
        $r1 = (new Role(1))->setName('n1');
        $r2 = (new Role(2))->setName('n2');
        $player->addRole($r1)->addRole($r2);

        $this->assertSame(['n1', 'n2'], $player->getRoleNames());
    }

    public function testHasRole()
    {
        $player = new Player();
        $role = new Role(1);
        $role->setName('role1');
        $player->addRole($role);

        $this->assertTrue($player->hasRole('role1'));
        $this->assertFalse($player->hasRole('role2'));
    }

    public function testAddGetRemoveCharacter()
    {
        $play = new Player();
        $c1 = new Character();
        $c2 = new Character();

        $this->assertSame([], $play->getCharacters());

        $play->addCharacter($c1);
        $play->addCharacter($c2);
        $this->assertSame([$c1, $c2], $play->getCharacters());

        $play->removeCharacter($c2);
        $this->assertSame([$c1], $play->getCharacters());
    }

    public function testGetCoreCharacters()
    {
        $player = new Player();
        $c1 = (new Character())->setName('c1')->setPlayer($player);
        $c2 = (new Character())->setName('c2')->setPlayer($player);
        $player->addCharacter($c1)->addCharacter($c2);

        $this->assertSame(2, count($player->getCoreCharacters()));
        $this->assertInstanceOf(CoreCharacter::class, $player->getCoreCharacters()[0]);
        $this->assertInstanceOf(CoreCharacter::class, $player->getCoreCharacters()[1]);
        $this->assertSame('c1', $player->getCoreCharacters()[0]->name);
        $this->assertSame('c2', $player->getCoreCharacters()[1]->name);
    }

    public function testHasCharacter()
    {
        $char1 = (new Character())->setId(1);
        $char2 = (new Character())->setId(2);

        $player = new Player();
        $player->addCharacter($char1);

        $this->assertTrue($player->hasCharacter($char1->getId()));
        $this->assertFalse($player->hasCharacter($char2->getId()));
    }

    public function testGetCharacter()
    {
        $char1 = (new Character())->setId(1);
        $char2 = (new Character())->setId(2);

        $player = new Player();
        $player->addCharacter($char1);

        $this->assertSame($char1, $player->getCharacter($char1->getId()));
        $this->assertNull($player->getCharacter($char2->getId()));
    }

    public function testHasCharacterInAllianceOrCorporation()
    {
        $alliance = (new Alliance())->setId(11);
        $corporation1 = (new Corporation())->setId(101);
        $corporation2 = (new Corporation())->setId(102);
        $player = new Player();
        $char1 = (new Character())->setId(1001);
        $char2 = (new Character())->setId(1001);
        $char3 = (new Character())->setId(1001);
        $corporation1->setAlliance($alliance);
        $player->addCharacter($char1);
        $player->addCharacter($char2);
        $player->addCharacter($char3);
        $char1->setCorporation($corporation1);
        $char2->setCorporation($corporation2);

        // player is member of alliance 11 and corporation 101, 102

        $this->assertFalse($player->hasCharacterInAllianceOrCorporation([], []));
        $this->assertTrue($player->hasCharacterInAllianceOrCorporation([11, 12], []));
        $this->assertTrue($player->hasCharacterInAllianceOrCorporation([], [101, 103]));
        $this->assertTrue($player->hasCharacterInAllianceOrCorporation([11, 12], [101, 103]));
        $this->assertFalse($player->hasCharacterInAllianceOrCorporation([12, 13], [103, 104]));
    }

    public function testHasCharacterWithInvalidTokenOlderThan()
    {
        $eveLogin = (new EveLogin())->setName(EveLogin::NAME_DEFAULT);
        $token1 = (new EsiToken())->setEveLogin($eveLogin)->setValidToken(true)
            ->setValidTokenTime(new \DateTime('now -10 seconds'));
        $token2 = (new EsiToken())->setEveLogin($eveLogin)->setValidToken(false)
            ->setValidTokenTime(new \DateTime('now -10 seconds'));
        $token3 = (new EsiToken())->setEveLogin($eveLogin)->setValidToken(false)
            ->setValidTokenTime(new \DateTime('now -36 hours'));
        $token4 = (new EsiToken())->setEveLogin($eveLogin)->setValidToken(false)
            ->setValidTokenTime(new \DateTime('now +12 hours'));
        $token5 = (new EsiToken())->setEveLogin($eveLogin)
            ->setValidTokenTime(new \DateTime('now -36 hours')); // validToken is null
        $token6 = (new EsiToken())->setEveLogin($eveLogin);
        $char1 = (new Character())->addEsiToken($token1);
        $char2 = (new Character())->addEsiToken($token2);
        $char3 = (new Character())->addEsiToken($token3);
        $char4 = (new Character())->addEsiToken($token4);
        $char5 = (new Character())->addEsiToken($token5);
        $char6 = (new Character())->addEsiToken($token6);

        $player1 = (new Player())->addCharacter($char1);
        $player2 = (new Player())->addCharacter($char2);
        $player3 = (new Player())->addCharacter($char1)->addCharacter($char3);
        $player4 = (new Player())->addCharacter($char1)->addCharacter($char4);
        $player5 = (new Player())->addCharacter($char5);
        $player6 = (new Player())->addCharacter($char6);
        $player7 = (new Player())->addCharacter(new Character());

        $this->assertFalse($player1->hasCharacterWithInvalidTokenOlderThan(24));
        $this->assertFalse($player2->hasCharacterWithInvalidTokenOlderThan(24)); // false because time is NOW

        $this->assertFalse($player3->hasCharacterWithInvalidTokenOlderThan(48));
        $this->assertTrue($player3->hasCharacterWithInvalidTokenOlderThan(24));
        $this->assertTrue($player3->hasCharacterWithInvalidTokenOlderThan(6));

        $this->assertFalse($player4->hasCharacterWithInvalidTokenOlderThan(6));

        $this->assertTrue($player5->hasCharacterWithInvalidTokenOlderThan(6)); // true because token is NULL

        $this->assertTrue($player2->hasCharacterWithInvalidTokenOlderThan(0)); // it's older or equal 0

        $this->assertTrue($player6->hasCharacterWithInvalidTokenOlderThan(123)); // no token time set

        $this->assertTrue($player7->hasCharacterWithInvalidTokenOlderThan(123)); // no token
    }

    public function testGetMain()
    {
        $player = new Player();
        $char1 = new Character();
        $char2 = new Character();
        $player->addCharacter($char1);
        $player->addCharacter($char2);

        $this->assertNull($player->getMain());

        $char1->setMain(true);

        $this->assertSame($char1, $player->getMain());
    }

    public function testAddGetRemoveGroupApplication()
    {
        $play = new Player();
        $a1 = new GroupApplication();
        $a2 = new GroupApplication();

        $this->assertSame([], $play->getGroupApplications());

        $play->addGroupApplication($a1);
        $play->addGroupApplication($a2);
        $this->assertSame([$a1, $a2], $play->getGroupApplications());

        $play->removeGroupApplication($a2);
        $this->assertSame([$a1], $play->getGroupApplications());
    }

    public function testAddGetRemoveGroup()
    {
        $play = new Player();
        $g1 = new Group();
        $g2 = new Group();

        $this->assertSame([], $play->getGroups());

        $play->addGroup($g1);
        $play->addGroup($g2);
        $this->assertSame([$g1, $g2], $play->getGroups());

        $play->removeGroup($g2);
        $this->assertSame([$g1], $play->getGroups());
    }

    public function testGetCoreGroups()
    {
        $player = (new Player())
            ->addGroup((new Group())->setName('g1'))
            ->addGroup((new Group())->setName('g2'))
        ;

        $coreGroups = $player->getCoreGroups();

        $this->assertSame(2, count($coreGroups));
        $this->assertInstanceOf(CoreGroup::class, $coreGroups[0]);
        $this->assertInstanceOf(CoreGroup::class, $coreGroups[1]);
        $this->assertSame('g1', $coreGroups[0]->name);
        $this->assertSame('g2', $coreGroups[1]->name);
        $this->assertSame(0, $coreGroups[0]->identifier);
        $this->assertSame(0, $coreGroups[1]->identifier);
    }

    public function testFindGroupById()
    {
        $group1 = new Group();
        $group2 = new Group();

        $rp = new \ReflectionProperty(Group::class, 'id');
        $rp->setAccessible(true);
        $rp->setValue($group1, 1);
        $rp->setValue($group2, 2);

        $player = new Player();
        $player->addGroup($group1);
        $player->addGroup($group2);

        $this->assertSame(2, $player->findGroupById(2)->getId());
        $this->assertNull($player->findGroupById(3));
    }

    public function testGetGroupIds()
    {
        $group1 = new Group();
        $group2 = new Group();

        $rp = new \ReflectionProperty(Group::class, 'id');
        $rp->setAccessible(true);
        $rp->setValue($group1, 1);
        $rp->setValue($group2, 2);

        $player = new Player();
        $player->addGroup($group1);
        $player->addGroup($group2);

        $this->assertSame([1, 2], $player->getGroupIds());
    }

    public function testHasGroup()
    {
        $group1 = new Group();
        $group2 = new Group();

        $rp = new \ReflectionProperty(Group::class, 'id');
        $rp->setAccessible(true);
        $rp->setValue($group1, 1);
        $rp->setValue($group2, 2);

        $player = new Player();
        $player->addGroup($group1);

        $this->assertTrue($player->hasGroup($group1->getId()));
        $this->assertFalse($player->hasGroup($group2->getId()));
    }

    public function testHasAnyGroup()
    {
        $group1 = new Group();
        $group2 = new Group();

        $rp = new \ReflectionProperty(Group::class, 'id');
        $rp->setAccessible(true);
        $rp->setValue($group1, 1);
        $rp->setValue($group2, 2);

        $player = new Player();
        $player->addGroup($group1);

        $this->assertTrue($player->hasAnyGroup([1, 2]));
        $this->assertFalse($player->hasAnyGroup([2, 3]));
    }

    public function testIsAllowedMember()
    {
        $player = new Player();
        $group1 = new Group();
        $group2 = new Group();
        $group3 = new Group();
        $group4 = new Group();

        $rp = new \ReflectionProperty(Group::class, 'id');
        $rp->setAccessible(true);
        $rp->setValue($group1, 1);
        $rp->setValue($group2, 2);
        $rp->setValue($group3, 3);
        $rp->setValue($group4, 4);

        $this->assertTrue($player->isAllowedMember($group1));

        $group1->addRequiredGroup($group2);
        $this->assertFalse($player->isAllowedMember($group1));

        $player->addGroup($group2);
        $this->assertTrue($player->isAllowedMember($group1));

        $group1->addRequiredGroup($group3);
        $this->assertTrue($player->isAllowedMember($group1));

        $group1->addForbiddenGroup($group4);
        $this->assertTrue($player->isAllowedMember($group1));

        $player->addGroup($group4);
        $this->assertFalse($player->isAllowedMember($group1));
    }

    public function testAddGetRemoveManagerGroups()
    {
        $play = new Player();
        $g1 = new Group();
        $g2 = new Group();

        $this->assertSame([], $play->getManagerGroups());

        $play->addManagerGroup($g1);
        $play->addManagerGroup($g2);
        $this->assertSame([$g1, $g2], $play->getManagerGroups());

        $play->removeManagerGroup($g2);
        $this->assertSame([$g1], $play->getManagerGroups());
    }

    public function testGetManagerCoreGroups()
    {
        $player = new Player();
        $player->addManagerGroup((new Group())->setName('g1'));
        $player->addManagerGroup((new Group())->setName('g2'));

        $this->assertSame(2, count($player->getManagerCoreGroups()));
        $this->assertInstanceOf(CoreGroup::class, $player->getManagerCoreGroups()[0]);
        $this->assertInstanceOf(CoreGroup::class, $player->getManagerCoreGroups()[1]);
        $this->assertSame('g1', $player->getManagerCoreGroups()[0]->name);
        $this->assertSame('g2', $player->getManagerCoreGroups()[1]->name);
    }

    public function testGetManagerGroupIds()
    {
        $group1 = new Group();
        $group2 = new Group();

        $rp = new \ReflectionProperty(Group::class, 'id');
        $rp->setAccessible(true);
        $rp->setValue($group1, 1);
        $rp->setValue($group2, 2);

        $player = new Player();
        $player->addManagerGroup($group1);
        $player->addManagerGroup($group2);

        $this->assertSame([1, 2], $player->getManagerGroupIds());
    }

    public function testHasManagerGroup()
    {
        $group1 = (new Group())->setName('g1');
        $group2 = (new Group())->setName('g2');
        $rp = new \ReflectionProperty(Group::class, 'id');
        $rp->setAccessible(true);
        $rp->setValue($group1, 1);
        $rp->setValue($group2, 2);

        $player = new Player();
        $player->addManagerGroup($group1);

        $this->assertTrue($player->hasManagerGroup($group1->getId()));
        $this->assertFalse($player->hasManagerGroup($group2->getId()));
    }

    public function testAddGetRemoveManagerApps()
    {
        $play = new Player();
        $a1 = new App();
        $a2 = new App();

        $this->assertSame([], $play->getManagerApps());

        $play->addManagerApp($a1);
        $play->addManagerApp($a2);
        $this->assertSame([$a1, $a2], $play->getManagerApps());

        $play->removeManagerApp($a2);
        $this->assertSame([$a1], $play->getManagerApps());
    }

    public function testAddGetRemoveRemovedCharacters()
    {
        $play = new Player();
        $rc1 = new RemovedCharacter();
        $rc2 = new RemovedCharacter();

        $this->assertSame([], $play->getRemovedCharacters());

        $play->addRemovedCharacter($rc1);
        $play->addRemovedCharacter($rc2);
        $this->assertSame([$rc1, $rc2], $play->getRemovedCharacters());

        $play->removeRemovedCharacter($rc2);
        $this->assertSame([$rc1], $play->getRemovedCharacters());
    }

    public function testAddGetIncomingCharacter()
    {
        $play = new Player();
        $rc1 = new RemovedCharacter();

        $this->assertSame([], $play->getIncomingCharacters());

        $play->addIncomingCharacter($rc1);
        $this->assertSame([$rc1], $play->getIncomingCharacters());
    }

    /**
     * @phan-suppress PhanTypeArraySuspiciousNullable
     * @phan-suppress PhanTypeMismatchArgumentNullableInternal
     */
    public function testToCoreAccount()
    {
        $player = (new Player())->setId(1)->setName('p');
        $this->assertNull($player->toCoreAccount());
        $this->assertSame(1, $player->toCoreAccount(false)->playerId);
        $this->assertSame('p', $player->toCoreAccount(false)->playerName);

        $character = (new Character())->setId(100);
        $player->addCharacter($character);
        $character->setPlayer($player);
        $this->assertNull($player->toCoreAccount());

        $character->setMain(true);
        $account1 = $player->toCoreAccount(false);
        $this->assertSame(1, $account1->playerId);
        $this->assertSame('p', $account1->playerName);
        $this->assertNull($account1->main);
        $this->assertNull($account1->characters);
        $this->assertNull($account1->memberGroups);
        $this->assertNull($account1->managerGroups);
        $this->assertNull($account1->roles);
        $account2 = $player->toCoreAccount();
        $this->assertSame(1, $account2->playerId);
        $this->assertSame('p', $account2->playerName);
        $this->assertInstanceOf(CoreCharacter::class, $account2->main);
        $this->assertSame(100, $account2->main->id);
        $this->assertSame(1, count($account2->characters));
        $this->assertInstanceOf(CoreCharacter::class, $account2->characters[0]);
        $this->assertSame(100, $account2->characters[0]->id);
        $this->assertSame([], $account2->memberGroups);
        $this->assertSame([], $account2->managerGroups);
        $this->assertSame([], $account2->roles);

        $player->addGroup((new Group())->setName('one'));
        $player->addManagerGroup((new Group())->setName('two'));
        $player->addRole((new Role(1))->setName('r'));
        $this->assertSame(1, count($player->toCoreAccount()->memberGroups));
        $this->assertSame(1, count($player->toCoreAccount()->managerGroups));
        $this->assertSame(1, count($player->toCoreAccount()->roles));
        $this->assertInstanceOf(CoreGroup::class, $player->toCoreAccount()->memberGroups[0]);
        $this->assertInstanceOf(CoreGroup::class, $player->toCoreAccount()->managerGroups[0]);
        $this->assertInstanceOf(CoreRole::class, $player->toCoreAccount()->roles[0]);
        $this->assertSame('one', $player->toCoreAccount()->memberGroups[0]->name);
        $this->assertSame('two', $player->toCoreAccount()->managerGroups[0]->name);
        $this->assertSame('r', $player->toCoreAccount()->roles[0]->name);
    }
}
