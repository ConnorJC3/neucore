<?php

/** @noinspection DuplicatedCode */

declare(strict_types=1);

namespace Tests\Unit\Repository;

use Doctrine\Persistence\ObjectManager;
use Neucore\Api;
use Neucore\Entity\Character;
use Neucore\Entity\Corporation;
use Neucore\Entity\CorporationMember;
use Neucore\Entity\EsiLocation;
use Neucore\Entity\EsiToken;
use Neucore\Entity\EsiType;
use Neucore\Entity\EveLogin;
use Neucore\Entity\Player;
use Neucore\Factory\RepositoryFactory;
use Neucore\Repository\CorporationMemberRepository;
use PHPUnit\Framework\TestCase;
use Tests\Helper;

class CorporationMemberRepositoryTest extends TestCase
{
    private static ObjectManager $om;

    private static \stdClass $values;

    public static function setupBeforeClass(): void
    {
        $helper = new Helper();
        $helper->emptyDb();
        self::$om = $helper->getObjectManager();

        self::$values = new \stdClass();
        self::$values->validtokenTime1 = new \DateTime('now -240 hours');
        self::$values->lastCheckedTime = new \DateTime('now -241 hours');
        self::$values->loginTime1 = new \DateTime('now -111 days +1 hour');
        self::$values->logoffTime1 = new \DateTime('now -111 days');
        self::$values->createdTime1 = new \DateTime("now -30 days");
        self::$values->updatedTime1 = new \DateTime("now -30 days");

        $eveLogin = (new EveLogin())->setName(EveLogin::NAME_DEFAULT);
        $location = (new EsiLocation())->setId(5040)->setName('A Station')->setCategory(EsiLocation::CATEGORY_STATION);
        $ship = (new EsiType())->setId(4030)->setName('A ship');
        $player1 = (new Player())->setName('Player 1');
        $char1 = (new Character())->setId(10)->setName('Char 1')->setPlayer($player1);
        $char2 = (new Character())->setId(20)->setName('Char 2')->setPlayer($player1)
            ->setCreated(self::$values->createdTime1)->setLastUpdate(self::$values->updatedTime1);
        $char5 = (new Character())->setId(50)->setName('Char 5')->setPlayer($player1);
        $char6 = (new Character())->setId(60)->setName('Char 6')->setPlayer($player1);
        $char7 = (new Character())->setId(70)->setName('Char 7')->setPlayer($player1);
        $token1 = (new EsiToken())->setEveLogin($eveLogin)->setRefreshToken('')->setAccessToken('')->setExpires(0)
            ->setValidToken(true)->setCharacter($char1);
        $token2 = (new EsiToken())->setEveLogin($eveLogin)->setRefreshToken('')->setAccessToken('')->setExpires(0)
            ->setValidToken(false)->setValidTokenTime(self::$values->validtokenTime1)
            ->setLastChecked(self::$values->lastCheckedTime)->setCharacter($char2);
        $token5 = (new EsiToken())->setEveLogin($eveLogin)->setRefreshToken('')->setAccessToken('')->setExpires(0)
            ->setValidToken(true)->setCharacter($char5);
        $token6 = (new EsiToken())->setEveLogin($eveLogin)->setRefreshToken('')->setAccessToken('')->setExpires(0)
            ->setCharacter($char6);
        $corp1 = (new Corporation())->setId(1)->setName('Corp 1')->setTicker('C1');
        $corp2 = (new Corporation())->setId(2)->setName('Corp 2')->setTicker('C2');
        $member1 = (new CorporationMember())->setId($char1->getId())->setName('Member 1')->setCorporation($corp1)
            ->setLogonDate(new \DateTime('now -112 days +1 hour'));
        $member1a = (new CorporationMember())->setId(101)->setName('Member 1a')->setCorporation($corp1)
            ->setLogonDate(new \DateTime('now -112 days +30 minutes'));
        $member2 = (new CorporationMember())->setId($char2->getId())->setName('Member 2')->setCorporation($corp1)
            ->setLocation($location)->setShipType($ship)
            ->setLogonDate(self::$values->loginTime1)->setLogoffDate(self::$values->logoffTime1)
            ->setStartDate(new \DateTime('2019-05-26 15:51:18'))
            ->setMissingCharacterMailSentDate(new \DateTime('2019-05-27 06:45:41'))
            ->setMissingCharacterMailSentResult(Api::MAIL_OK)->setMissingCharacterMailSentNumber(2);
        $member3 = (new CorporationMember())->setId(30)->setName('Member 3')->setCorporation($corp1)
            ->setLogonDate(new \DateTime('now -110 days +1 hour'));
        $member4 = (new CorporationMember())->setId(40)->setName('Member 4')->setCorporation($corp2)
            ->setLogonDate(new \DateTime('now -111 days +30 minutes'));
        $member5 = (new CorporationMember())->setId($char5->getId())->setName('Member 5')->setCorporation($corp2)
            ->setLogonDate(new \DateTime('now -110 days +30 minutes'));
        $member6 = (new CorporationMember())->setId($char6->getId())->setName('Member 6')->setCorporation($corp2)
            ->setLogonDate(new \DateTime('2022-11-05 14:03:36'));
        $member7 = (new CorporationMember())->setId($char7->getId())->setName('Member 7')->setCorporation($corp2)
            ->setLogonDate(new \DateTime('2022-11-05 14:03:37'));

        self::$om->persist($eveLogin);
        self::$om->persist($location);
        self::$om->persist($ship);
        self::$om->persist($player1);
        self::$om->persist($char1);
        self::$om->persist($char2);
        self::$om->persist($char5);
        self::$om->persist($char6);
        self::$om->persist($char7);
        self::$om->persist($token1);
        self::$om->persist($token2);
        self::$om->persist($token5);
        self::$om->persist($token6);
        self::$om->persist($corp1);
        self::$om->persist($corp2);
        self::$om->persist($member1);
        self::$om->persist($member1a);
        self::$om->persist($member2);
        self::$om->persist($member3);
        self::$om->persist($member4);
        self::$om->persist($member5);
        self::$om->persist($member6);
        self::$om->persist($member7);

        self::$om->flush();
        self::$om->clear();

        self::$values->playerId1 = $player1->getId();
    }

    public function testResetCriteria()
    {
        $repository = (new RepositoryFactory(self::$om))->getCorporationMemberRepository();

        $repository->setAccount(false);
        $repository->setActive(110);
        $repository->setInactive(110);
        $repository->setTokenStatus($repository::TOKEN_STATUS_INVALID);
        $repository->setTokenChanged(100);
        $repository->setMailCount(2);
        $this->assertSame(0, count($repository->findMatching(1)));

        $repository->resetCriteria();

        $actual = $repository->findMatching(1);

        $this->assertSame(4, count($repository->findMatching(1)));

        $this->assertSame('Member 3', $actual[0]->getName());
        $this->assertSame('Member 2', $actual[1]->getName());
        $this->assertSame('Member 1', $actual[2]->getName());
        $this->assertSame('Member 1a', $actual[3]->getName());
    }

    public function testFindMatchingActive()
    {
        $repository = (new RepositoryFactory(self::$om))->getCorporationMemberRepository();

        $actual1 = $repository->setActive(109)->findMatching(1);
        $actual2 = $repository->setActive(110)->findMatching(1);
        $actual3 = $repository->setActive(112)->findMatching(1);

        $this->assertSame(0, count($actual1));
        $this->assertSame(1, count($actual2));
        $this->assertSame(4, count($actual3));

        $this->assertSame('Member 3', $actual2[0]->getName());
        $this->assertSame('Member 3', $actual3[0]->getName());
        $this->assertSame('Member 2', $actual3[1]->getName());
        $this->assertSame('Member 1', $actual3[2]->getName());
        $this->assertSame('Member 1a', $actual3[3]->getName());
    }

    public function testFindMatchingInactive()
    {
        $repository = (new RepositoryFactory(self::$om))->getCorporationMemberRepository();

        $actual1 = $repository->setInactive(109)->findMatching(1);
        $actual2 = $repository->setInactive(110)->findMatching(1);
        $actual3 = $repository->setInactive(112)->findMatching(1);

        $this->assertSame(4, count($actual1));
        $this->assertSame(3, count($actual2));
        $this->assertSame(0, count($actual3));

        $this->assertSame('Member 3', $actual1[0]->getName());
        $this->assertSame('Member 2', $actual1[1]->getName());
        $this->assertSame('Member 1', $actual1[2]->getName());
        $this->assertSame('Member 1a', $actual1[3]->getName());
        $this->assertSame('Member 2', $actual2[0]->getName());
        $this->assertSame('Member 1', $actual2[1]->getName());
        $this->assertSame('Member 1a', $actual2[2]->getName());
    }

    public function testFindMatchingActiveRange()
    {
        $repository = (new RepositoryFactory(self::$om))->getCorporationMemberRepository();

        $actual = $repository->setInactive(110)->setActive(111)->findMatching(1);

        $this->assertSame([[
            'id' => 20,
            'name' => 'Member 2',
            'location' => [
                'id' => 5040,
                'name' => 'A Station',
                'category' => EsiLocation::CATEGORY_STATION,
            ],
            'logoffDate' => date_format(self::$values->logoffTime1, Api::DATE_FORMAT),
            'logonDate' => date_format(self::$values->loginTime1, Api::DATE_FORMAT),
            'shipType' => [
                'id' => 4030,
                'name' => 'A ship',
            ],
            'startDate' => '2019-05-26T15:51:18Z',
            'missingCharacterMailSentDate' => '2019-05-27T06:45:41Z',
            'missingCharacterMailSentResult' => Api::MAIL_OK,
            'missingCharacterMailSentNumber' => 2,
            'character' => [
                'id' => 20,
                'name' => 'Char 2',
                'main' => false,
                'created' => date_format(self::$values->createdTime1, Api::DATE_FORMAT),
                'lastUpdate' => date_format(self::$values->updatedTime1, Api::DATE_FORMAT),
                'validToken' => false,
                'validTokenTime' => date_format(self::$values->validtokenTime1, Api::DATE_FORMAT),
                'tokenLastChecked' => date_format(self::$values->lastCheckedTime, Api::DATE_FORMAT),
            ],
            'player' => [
                'id' => self::$values->playerId1,
                'name' => 'Player 1',
            ],
        ]], json_decode((string) json_encode($actual), true));
    }

    public function testFindMatchingWithOutAccount()
    {
        $repository = (new RepositoryFactory(self::$om))->getCorporationMemberRepository();

        $actual1 = $repository->setAccount(true)->findMatching(1);
        $actual2 = $repository->setAccount(false)->findMatching(1);

        $this->assertSame(2, count($actual1));
        $this->assertSame(2, count($actual2));

        $this->assertSame('Member 2', $actual1[0]->getName());
        $this->assertSame('Member 1', $actual1[1]->getName());
        $this->assertSame('Char 2', $actual1[0]->getCharacter()->getName());
        $this->assertSame('Char 1', $actual1[1]->getCharacter()->getName());
        $this->assertSame('Member 3', $actual2[0]->getName());
        $this->assertSame('Member 1a', $actual2[1]->getName());
    }

    public function testFindMatchingTokenStatus()
    {
        $repository = (new RepositoryFactory(self::$om))->getCorporationMemberRepository();

        $actual1 = $repository->setTokenStatus(CorporationMemberRepository::TOKEN_STATUS_VALID)->findMatching(1);
        $actual2 = $repository->setTokenStatus(CorporationMemberRepository::TOKEN_STATUS_INVALID)->findMatching(1);
        $actual3 = $repository->setTokenStatus(CorporationMemberRepository::TOKEN_STATUS_NONE)->findMatching(2);

        $this->assertSame(1, count($actual1));
        $this->assertSame(1, count($actual2));
        $this->assertSame(2, count($actual3));

        $this->assertSame('Char 1', $actual1[0]->getCharacter()->getName());
        $this->assertSame('Char 2', $actual2[0]->getCharacter()->getName());
        $this->assertSame('Char 7', $actual3[0]->getCharacter()->getName());
        $this->assertSame('Char 6', $actual3[1]->getCharacter()->getName());
    }

    public function testFindMatchingTokenChanged()
    {
        $repository = (new RepositoryFactory(self::$om))->getCorporationMemberRepository();

        $actual = $repository->setTokenChanged(9)->findMatching(1);

        $this->assertSame(1, count($actual));
        $this->assertSame('Char 2', $actual[0]->getCharacter()->getName());
    }

    public function testFindMatchingMailCount()
    {
        $repository = (new RepositoryFactory(self::$om))->getCorporationMemberRepository();

        $actual = $repository->setMailCount(2)->findMatching(1);

        $this->assertSame(1, count($actual));
        $this->assertSame('Char 2', $actual[0]->getCharacter()->getName());
    }

    public function testRemoveFormerMembers()
    {
        $repository = (new RepositoryFactory(self::$om))->getCorporationMemberRepository();
        $actual = $repository->removeFormerMembers(1, [10, 30]);
        $this->assertSame(2, $actual); // removed ids: 20, 101
    }

    public function testFindByCorporationsWithoutAccount()
    {
        $repository = (new RepositoryFactory(self::$om))->getCorporationMemberRepository();

        $actual0 = $repository->findByCorporationsWithoutAccountAndActive([1, 2], 111);
        $this->assertSame(2, count($actual0));
        $this->assertSame(30, $actual0[0]->getId());
        $this->assertSame(40, $actual0[1]->getId());

        $actual1 = $repository->findByCorporationsWithoutAccountAndActive([1, 2], 111, 1);
        $this->assertSame(1, count($actual1));
        $this->assertSame(30, $actual1[0]->getId());

        $actual2 = $repository->findByCorporationsWithoutAccountAndActive([1, 2], 111, 1, 1);
        $this->assertSame(1, count($actual2));
        $this->assertSame(40, $actual2[0]->getId());
    }

    public function testFetchCorporationIds()
    {
        $repository = (new RepositoryFactory(self::$om))->getCorporationMemberRepository();
        $ids = $repository->fetchCorporationIds();
        $this->assertSame([1, 2], $ids);
    }
}
