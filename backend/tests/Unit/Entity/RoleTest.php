<?php

/** @noinspection DuplicatedCode */

declare(strict_types=1);

namespace Tests\Unit\Entity;

use Neucore\Entity\App;
use Neucore\Entity\Group;
use Neucore\Entity\Player;
use Neucore\Entity\Role;
use PHPUnit\Framework\TestCase;

class RoleTest extends TestCase
{
    public function testJsonSerialize()
    {
        $role = new Role(1);
        $role->setName('r.name');

        $this->assertSame('r.name', json_decode((string) json_encode($role), true));
    }

    public function testGetId()
    {
        $this->assertSame(1, (new Role(1))->getId());
    }

    public function testSetGetName()
    {
        $role = new Role(1);
        $role->setName('nam');
        $this->assertSame('nam', $role->getName());
    }

    public function testAddGetRemoveCharacter()
    {
        $role = new Role(1);
        $p1 = new Player();
        $p2 = new Player();

        $this->assertSame([], $role->getPlayers());

        $role->addPlayer($p1);
        $role->addPlayer($p2);
        $this->assertSame([$p1, $p2], $role->getPlayers());

        $role->removePlayer($p2);
        $role->removePlayer($p1);
        $this->assertSame([], $role->getPlayers());
    }

    public function testAddGetRemoveApp()
    {
        $role = new Role(1);
        $a1 = new App();
        $a2 = new App();

        $this->assertSame([], $role->getApps());

        $role->addApp($a1);
        $role->addApp($a2);
        $this->assertSame([$a1, $a2], $role->getApps());

        $role->removeApp($a2);
        $this->assertSame([$a1], $role->getApps());
    }

    public function testAddGetRemoveRequiredGroup()
    {
        $role = new Role(1);
        $g1 = new Group();
        $g2 = new Group();

        $this->assertSame([], $role->getRequiredGroups());

        $role->addRequiredGroup($g1);
        $role->addRequiredGroup($g2);
        $this->assertSame([$g1, $g2], $role->getRequiredGroups());

        $role->removeRequiredGroup($g2);
        $this->assertSame([$g1], $role->getRequiredGroups());
    }
}
