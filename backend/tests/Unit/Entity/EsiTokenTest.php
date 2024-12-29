<?php

declare(strict_types=1);

namespace Tests\Unit\Entity;

use Neucore\Api;
use Neucore\Entity\Character;
use Neucore\Entity\Corporation;
use Neucore\Entity\EsiToken;
use Neucore\Entity\EveLogin;
use Neucore\Entity\Player;
use Neucore\Plugin\Data\CoreEsiToken;
use PHPUnit\Framework\TestCase;

class EsiTokenTest extends TestCase
{
    public function testJsonSerialize()
    {
        $token = new EsiToken();
        $token->setValidToken(true);

        $this->assertSame([
            'eveLoginId' => 0,
            'characterId' => 0,
            'playerId' => 0,
            'validToken' => true,
            'validTokenTime' => $token->getValidTokenTime()->format(Api::DATE_FORMAT),
            'hasRoles' => null,
            'lastChecked' => null,
        ], json_decode((string) json_encode($token), true));

        $token->setEveLogin((new EveLogin())->setId(1));
        $token->setLastChecked(new \DateTime());
        $this->assertSame([
            'eveLoginId' => 1,
            'characterId' => 0,
            'playerId' => 0,
            'validToken' => true,
            'validTokenTime' => $token->getValidTokenTime()->format(Api::DATE_FORMAT),
            'hasRoles' => null,
            'lastChecked' => $token->getLastChecked()->format(Api::DATE_FORMAT),
        ], json_decode((string) json_encode($token), true));
    }

    public function testSetGetId()
    {
        $this->assertNull((new EsiToken())->getId());
        $this->assertSame(5, (new EsiToken())->setId(5)->getId());
    }

    public function testSetGetCharacter()
    {
        $token = new EsiToken();
        $character = new Character();
        $token->setCharacter($character);
        $this->assertSame($character, $token->getCharacter());
    }

    public function testSetGetEveLogin()
    {
        $token = new EsiToken();
        $login = new EveLogin();
        $token->setEveLogin($login);
        $this->assertSame($login, $token->getEveLogin());
    }

    public function testSetGetRefreshToken()
    {
        $token = new EsiToken();
        $token->setRefreshToken('dfg');
        $this->assertSame('dfg', $token->getRefreshToken());
    }

    public function testSetGetAccessToken()
    {
        $token = new EsiToken();
        $token->setAccessToken('123');
        $this->assertSame('123', $token->getAccessToken());
    }

    public function testSetGetExpires()
    {
        $token = new EsiToken();
        $token->setExpires(456);
        $this->assertSame(456, $token->getExpires());
    }

    public function testSetGetValidToken()
    {
        $token = new EsiToken();

        $this->assertNull($token->getValidToken());
        $this->assertTrue($token->setValidToken(true)->getValidToken());
        $this->assertFalse($token->setValidToken(false)->getValidToken());
        $this->assertNull($token->setValidToken()->getValidToken());
    }

    public function testSetValidTokenUpdatesTime()
    {
        $token = new EsiToken();

        $this->assertNull($token->getValidTokenTime());
        $this->assertNull($token->getValidToken());

        $token->setValidToken();
        $this->assertNull($token->getValidTokenTime());

        $token->setValidToken(false);
        $time1 = $token->getValidTokenTime();
        $this->assertNotNull($time1);

        $token->setValidToken(true);
        $time2 = $token->getValidTokenTime();
        $this->assertNotSame($time1, $time2);
        $this->assertNotNull($time2);

        $token->setValidToken();
        $time3 = $token->getValidTokenTime();
        $this->assertNotSame($time2, $time3);
        $this->assertNotNull($token->getValidTokenTime());
    }

    public function testSetGetValidTokenTime()
    {
        $dt1 = new \DateTime('2018-04-26 18:59:35');

        $token = new EsiToken();
        $token->setValidTokenTime($dt1);
        $dt2 = $token->getValidTokenTime();

        $this->assertNotSame($dt1, $dt2);
        $this->assertSame('2018-04-26T18:59:35+00:00', $dt2->format(\DateTimeInterface::ATOM));
    }

    public function testSetGetLastChecked()
    {
        $dt1 = new \DateTime('2022-05-27 15:59:36');

        $token = new EsiToken();
        $token->setLastChecked($dt1);
        $dt2 = $token->getLastChecked();

        $this->assertNotSame($dt1, $dt2);
        $this->assertSame('2022-05-27T15:59:36+00:00', $dt2->format(\DateTimeInterface::ATOM));
    }

    public function testSetGetHasRoles()
    {
        $token = new EsiToken();

        $this->assertNull($token->getHasRoles());
        $this->assertTrue($token->setHasRoles(true)->getHasRoles());
        $this->assertFalse($token->setHasRoles(false)->getHasRoles());
        $this->assertNull($token->setHasRoles()->getHasRoles());
    }

    public function testToCoreEsiToken()
    {
        $this->assertNull((new EsiToken())->toCoreEsiToken(true));
        $this->assertNull((new EsiToken())->setCharacter(new Character())->toCoreEsiToken(true));
        $this->assertNull((new EsiToken())->setEveLogin(new EveLogin())->toCoreEsiToken(true));

        $token = (new EsiToken())
            ->setCharacter(
                (new Character())
                    ->setId(102030)
                    ->setName('char')
                    ->setPlayer((new Player())->setId(14)->setName('play'))
                    ->setCorporation((new Corporation())->setId(131)->setName('corp')),
            )
            ->setEveLogin(
                (new EveLogin())
                    ->setName('login.one')
                    ->setEsiScopes('scope.one scope.two')
                    ->setEveRoles(['role1', 'role2']),
            )
            ->setValidToken(true)
            ->setHasRoles(false)
            ->setLastChecked(new \DateTime())
        ;

        $result1 = $token->toCoreEsiToken(true);
        $this->assertInstanceOf(CoreEsiToken::class, $result1);
        $this->assertSame(102030, $result1->character->id);
        $this->assertSame('corp', $result1->character->corporationName);
        $this->assertSame('login.one', $result1->eveLoginName);
        $this->assertSame(['scope.one', 'scope.two'], $result1->esiScopes);
        $this->assertSame(['role1', 'role2'], $result1->eveRoles);
        $this->assertTrue($result1->valid);
        $this->assertInstanceOf(\DateTime::class, $result1->validStatusChanged);
        $this->assertFalse($result1->hasRoles);
        $this->assertInstanceOf(\DateTime::class, $result1->lastChecked);

        $result2 = $token->toCoreEsiToken(false);
        $this->assertInstanceOf(CoreEsiToken::class, $result2);
        $this->assertSame(102030, $result2->character->id);
        $this->assertNull($result2->character->corporationName);
    }
}
