<?php

/** @noinspection DuplicatedCode */

declare(strict_types=1);

namespace Tests\Unit\Entity;

use Neucore\Entity\App;
use Neucore\Entity\Alliance;
use Neucore\Entity\Corporation;
use Neucore\Entity\Group;
use Neucore\Entity\GroupApplication;
use Neucore\Entity\Player;
use Neucore\Plugin\Data\CoreGroup;
use PHPUnit\Framework\TestCase;

class GroupTest extends TestCase
{
    public function testJsonSerialize()
    {
        $group = new Group();
        $group->setName('g.name');

        $this->assertSame(
            ['id' => null, 'name' => 'g.name', 'description' => null,
                'visibility' => Group::VISIBILITY_PRIVATE, 'autoAccept' => false, 'isDefault' => false],
            json_decode((string) json_encode($group), true),
        );
    }

    public function testGetId()
    {
        $this->assertSame(0, (new Group())->getId());
    }

    public function testSetGetName()
    {
        $group = new Group();
        $group->setName('nam');
        $this->assertSame('nam', $group->getName());
    }

    public function testSetGetDescription()
    {
        $group = new Group();
        $group->setDescription("Hell\no");
        $this->assertSame("Hell\no", $group->getDescription());
    }

    public function testSetVisibilityException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Parameter must be one of ');

        $group = new Group();
        $group->setVisibility('invalid');
    }

    public function testSetGetVisibility()
    {
        $group = new Group();
        $this->assertsame(Group::VISIBILITY_PRIVATE, $group->getVisibility());
        $group->setVisibility(Group::VISIBILITY_PUBLIC);
        $this->assertsame(Group::VISIBILITY_PUBLIC, $group->getVisibility());
    }

    public function testSetGetAutoAccept()
    {
        $group = new Group();
        $this->assertFalse($group->getAutoAccept());
        $group->setAutoAccept(true);
        $this->assertTrue($group->getAutoAccept());
    }


    public function testSetGetIsDefault()
    {
        $group = new Group();
        $this->assertFalse($group->getIsDefault());
        $group->setIsDefault(true);
        $this->assertTrue($group->getIsDefault());
    }

    public function testAddGetRemoveApplication()
    {
        $group = new Group();
        $a1 = new GroupApplication();
        $a2 = new GroupApplication();

        $this->assertSame([], $group->getApplications());

        $group->addApplication($a1);
        $group->addApplication($a2);
        $this->assertSame([$a1, $a2], $group->getApplications());

        $group->removeApplication($a2);
        $this->assertSame([$a1], $group->getApplications());
    }

    public function testAddGetRemovePlayer()
    {
        $this->assertSame([], (new Group())->getPlayers());
    }

    public function testAddGetRemoveManager()
    {
        $group = new Group();
        $p1 = new Player();
        $p2 = new Player();

        $this->assertSame([], $group->getManagers());

        $group->addManager($p1);
        $group->addManager($p2);
        $this->assertSame([$p1, $p2], $group->getManagers());

        $group->removeManager($p2);
        $this->assertSame([$p1], $group->getManagers());
    }

    public function testAddGetRemoveApp()
    {
        $group = new Group();
        $a1 = new App();
        $a2 = new App();

        $this->assertSame([], $group->getApps());

        $group->addApp($a1);
        $group->addApp($a2);
        $this->assertSame([$a1, $a2], $group->getApps());

        $group->removeApp($a2);
        $group->removeApp($a1);
        $this->assertSame([], $group->getApps());
    }

    public function testAddGetRemoveCorporation()
    {
        $group = new Group();
        $c1 = new Corporation();
        $c2 = new Corporation();

        $this->assertSame([], $group->getCorporations());

        $group->addCorporation($c1);
        $group->addCorporation($c2);
        $this->assertSame([$c1, $c2], $group->getCorporations());

        $group->removeCorporation($c2);
        $this->assertSame([$c1], $group->getCorporations());
    }

    public function testAddGetRemoveAlliance()
    {
        $group = new Group();
        $a1 = new Alliance();
        $a2 = new Alliance();

        $this->assertSame([], $group->getAlliances());

        $group->addAlliance($a1);
        $group->addAlliance($a2);
        $this->assertSame([$a1, $a2], $group->getAlliances());

        $group->removeAlliance($a2);
        $this->assertSame([$a1], $group->getAlliances());
    }

    public function testAddGetRemoveRequiredGroups()
    {
        $group = new Group();
        $required1 = new Group();
        $required2 = new Group();

        $this->assertSame([], $group->getRequiredGroups());

        $group->addRequiredGroup($required1);
        $group->addRequiredGroup($required2);
        $this->assertSame([$required1, $required2], $group->getRequiredGroups());

        $group->removeRequiredGroup($required1);
        $this->assertSame([$required2], $group->getRequiredGroups());
    }

    public function testAddGetRemoveForbiddenGroups()
    {
        $group = new Group();
        $forbidden1 = new Group();
        $forbidden2 = new Group();

        $this->assertSame([], $group->getForbiddenGroups());

        $group->addForbiddenGroup($forbidden1);
        $group->addForbiddenGroup($forbidden2);
        $this->assertSame([$forbidden1, $forbidden2], $group->getForbiddenGroups());

        $group->removeForbiddenGroup($forbidden1);
        $this->assertSame([$forbidden2], $group->getForbiddenGroups());
    }

    public function testToCoreGroup()
    {
        $group = (new Group())->setName('G1');

        $this->assertInstanceOf(CoreGroup::class, $group->toCoreGroup());
        $this->assertSame(0, $group->toCoreGroup()->identifier);
        $this->assertSame('G1', $group->toCoreGroup()->name);
    }
}
