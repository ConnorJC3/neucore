<?php
/** @noinspection DuplicatedCode */

declare(strict_types=1);

namespace Tests\Unit\Entity;

use Neucore\Entity\App;
use Neucore\Entity\EveLogin;
use Neucore\Entity\Group;
use Neucore\Entity\Player;
use Neucore\Entity\Role;
use Neucore\Util\Crypto;
use PHPUnit\Framework\TestCase;

class AppTest extends TestCase
{
    public function testJsonSerialize()
    {
        $app = new App();
        $app->setName('test app');

        $this->assertSame([
            'id' => null,
            'name' => 'test app',
            'groups' => [],
            'roles' => [],
            'eveLogins' => [],
        ], json_decode((string) json_encode($app), true));
    }

    public function testGetId()
    {
        $this->assertSame(0, (new App)->getId());
    }

    public function testSetGetName()
    {
        $app = new App();
        $app->setName('nam');
        $this->assertSame('nam', $app->getName());
    }

    public function testSetGetSecret()
    {
        $app = new App();
        $pw = password_hash('00h', Crypto::PASSWORD_HASH);
        $app->setSecret($pw);
        $this->assertSame($pw, $app->getSecret());
    }

    public function testAddGetRemoveRole()
    {
        $app = new App();
        $r1 = new Role(1);
        $r2 = new Role(2);

        $this->assertSame([], $app->getRoles());

        $app->addRole($r1);
        $app->addRole($r2);
        $this->assertSame([$r1, $r2], $app->getRoles());

        $app->removeRole($r2);
        $this->assertSame([$r1], $app->getRoles());
    }

    public function testGetRoleNames()
    {
        $app = new App();
        $r1 = (new Role(1))->setName('n1');
        $r2 = (new Role(2))->setName('n2');
        $app->addRole($r1)->addRole($r2);

        $this->assertSame(['n1', 'n2'], $app->getRoleNames());
    }

    public function testHasRole()
    {
        $app = new App();
        $role = new Role(1);
        $role->setName('role1');
        $app->addRole($role);

        $this->assertTrue($app->hasRole('role1'));
        $this->assertFalse($app->hasRole('role2'));
    }

    public function testAddGetRemoveGroup()
    {
        $app = new App();
        $g1 = new Group();
        $g2 = new Group();

        $this->assertSame([], $app->getGroups());

        $app->addGroup($g1);
        $app->addGroup($g2);
        $this->assertSame([$g1, $g2], $app->getGroups());

        $app->removeGroup($g2);
        $this->assertSame([$g1], $app->getGroups());
    }

    public function testAddGetRemoveManager()
    {
        $app = new App();
        $p1 = new Player();
        $p2 = new Player();

        $this->assertSame([], $app->getManagers());

        $app->addManager($p1);
        $app->addManager($p2);
        $this->assertSame([$p1, $p2], $app->getManagers());

        $app->removeManager($p2);
        $this->assertSame([$p1], $app->getManagers());
    }

    public function testIsManager()
    {
        $app = new App();
        $pl1 = new Player();
        $pl2 = new Player();

        $c1 = new \ReflectionClass($pl1);
        $p1 = $c1->getProperty("id");
        $p1->setAccessible(true);
        $p1->setValue($pl1, 1);

        $c2 = new \ReflectionClass($pl2);
        $p2 = $c2->getProperty("id");
        $p2->setAccessible(true);
        $p2->setValue($pl2, 2);

        $app->addManager($pl1);

        $this->assertTrue($app->isManager($pl1));
        $this->assertFalse($app->isManager($pl2));
    }

    public function testAddGetRemoveEveLogin()
    {
        $app = new App();
        $el1 = new EveLogin();
        $el2 = new EveLogin();

        $this->assertSame([], $app->getEveLogins());

        $app->addEveLogin($el1);
        $app->addEveLogin($el2);
        $this->assertSame([$el1, $el2], $app->getEveLogins());

        $app->removeEveLogin($el2);
        $this->assertSame([$el1], $app->getEveLogins());
    }
}
