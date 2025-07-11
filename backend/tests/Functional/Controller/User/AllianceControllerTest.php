<?php

/** @noinspection DuplicatedCode */

declare(strict_types=1);

namespace Tests\Functional\Controller\User;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\ObjectManager;
use Neucore\Entity\Alliance;
use Neucore\Entity\Group;
use Neucore\Entity\Role;
use Neucore\Factory\HttpClientFactoryInterface;
use Neucore\Factory\RepositoryFactory;
use GuzzleHttp\Psr7\Response;
use Monolog\Logger;
use Monolog\Handler\TestHandler;
use Neucore\Repository\AllianceRepository;
use Psr\Log\LoggerInterface;
use Tests\Functional\WebTestCase;
use Tests\Helper;
use Tests\Client;
use Tests\HttpClientFactory;
use Tests\WriteErrorListener;

class AllianceControllerTest extends WebTestCase
{
    private static WriteErrorListener $writeErrorListener;

    private Helper $helper;

    private EntityManagerInterface $em;

    private int $alliId1;

    private int $groupId1;

    private int $groupId2;

    private AllianceRepository $alliRepo;

    private Client $client;

    private LoggerInterface $log;

    public static function setupBeforeClass(): void
    {
        self::$writeErrorListener = new WriteErrorListener();
    }

    protected function setUp(): void
    {
        $_SESSION = null;

        $this->helper = new Helper();
        $this->em = $this->helper->getEm();

        $this->alliRepo = (new RepositoryFactory($this->em))->getAllianceRepository();

        $this->log = new Logger('Test');
        $this->log->pushHandler(new TestHandler());
        $this->client = new Client();
    }

    public function tearDown(): void
    {
        $this->em->getEventManager()->removeEventListener(Events::onFlush, self::$writeErrorListener);
    }

    public function testFind403()
    {
        $this->setupDb();

        $response1 = $this->runApp('GET', '/api/user/alliance/find/abc');
        $this->assertSame(403, $response1->getStatusCode());

        $this->loginUser(6); # not a group-admin
        $response2 = $this->runApp('GET', '/api/user/alliance/find/abc');
        $this->assertSame(403, $response2->getStatusCode());
    }

    public function testFind200()
    {
        $this->setupDb();
        $this->loginUser(7);

        $response1 = $this->runApp('GET', '/api/user/alliance/find/li%20'); // only 2 chars
        $this->assertSame(200, $response1->getStatusCode());
        $this->assertSame([], $this->parseJsonBody($response1));

        $response2 = $this->runApp('GET', '/api/user/alliance/find/li%202');
        $this->assertSame(200, $response2->getStatusCode());
        $this->assertSame([[
            'id' => 222,
            'name' => '1 alli 2',
            'ticker' => 't200',
        ]], $this->parseJsonBody($response2));

        $response3 = $this->runApp('GET', '/api/user/alliance/find/300');
        $this->assertSame(200, $response3->getStatusCode());
        $this->assertSame([[
            'id' => 333,
            'name' => 'alli 3',
            'ticker' => 't300',
        ]], $this->parseJsonBody($response3));
    }

    public function testAlliances403()
    {
        $this->setupDb();

        $response1 = $this->runApp('POST', '/api/user/alliance/alliances');
        $this->assertSame(403, $response1->getStatusCode());

        $this->loginUser(6); # not a group-admin
        $response2 = $this->runApp('POST', '/api/user/alliance/alliances');
        $this->assertSame(403, $response2->getStatusCode());
    }

    public function testAlliances400()
    {
        $this->setUpDb();
        $this->loginUser(7);

        $response = $this->runApp(
            'POST',
            '/api/user/alliance/alliances',
            null,
            ['Content-Type' => 'application/json'],
        );

        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testAlliances200()
    {
        $this->setUpDb();
        $this->loginUser(7);

        $response = $this->runApp(
            'POST',
            '/api/user/alliance/alliances',
            [222, 111],
            ['Content-Type' => 'application/json'],
        );

        $this->assertEquals(200, $response->getStatusCode());
        $body = $this->parseJsonBody($response);
        $expected = [
            ['id' => 222, 'name' => '1 alli 2', 'ticker' => 't200'],
            ['id' => 111, 'name' => '2 alli 1', 'ticker' => 't100'],
        ];
        $this->assertSame($expected, $body);
    }

    public function testWithGroups403()
    {
        $this->setupDb();

        $response = $this->runApp('GET', '/api/user/alliance/with-groups');
        $this->assertEquals(403, $response->getStatusCode());

        $this->loginUser(6); # not a user-admin

        $response = $this->runApp('GET', '/api/user/alliance/with-groups');
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testWithGroups200()
    {
        $this->setupDb();
        $this->loginUser(7);

        $response = $this->runApp('GET', '/api/user/alliance/with-groups');
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertSame(
            [
                ['id' => 222, 'name' => '1 alli 2', 'ticker' => 't200', 'groups' => [
                    ['id' => $this->groupId1, 'name' => 'group 1', 'description' => null,
                        'visibility' => Group::VISIBILITY_PRIVATE, 'autoAccept' => false,
                        'isDefault' => false, 'isAutoManaged' => null],
                    ['id' => $this->groupId2, 'name' => 'group 2', 'description' => null,
                        'visibility' => Group::VISIBILITY_PRIVATE, 'autoAccept' => false,
                        'isDefault' => false, 'isAutoManaged' => null],
                ]],
                ['id' => 111, 'name' => '2 alli 1', 'ticker' => 't100', 'groups' => [
                    ['id' => $this->groupId1, 'name' => 'group 1', 'description' => null,
                        'visibility' => Group::VISIBILITY_PRIVATE, 'autoAccept' => false,
                        'isDefault' => false, 'isAutoManaged' => null],
                ]],
            ],
            $this->parseJsonBody($response),
        );
    }

    public function testAdd403()
    {
        $this->setupDb();

        $response = $this->runApp('POST', '/api/user/alliance/add/123');
        $this->assertEquals(403, $response->getStatusCode());

        $this->loginUser(6); # not a user-admin

        $response = $this->runApp('POST', '/api/user/alliance/add/123');
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testAdd400()
    {
        $this->setupDb();
        $this->loginUser(7);

        $this->client->setResponse(new Response(400));

        $response = $this->runApp(
            'POST',
            '/api/user/alliance/add/123456789123',
            null,
            null,
            [
                HttpClientFactoryInterface::class => new HttpClientFactory($this->client),
                LoggerInterface::class => $this->log,
            ],
        );

        $this->assertEquals(400, $response->getStatusCode());
    }

    public function testAdd404()
    {
        $this->setupDb();
        $this->loginUser(7);

        $this->client->setResponse(new Response(404));

        $response = $this->runApp(
            'POST',
            '/api/user/alliance/add/123',
            null,
            null,
            [
                HttpClientFactoryInterface::class => new HttpClientFactory($this->client),
                LoggerInterface::class => $this->log,
            ],
        );

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testAdd409()
    {
        $this->setupDb();
        $this->loginUser(7);

        $response = $this->runApp('POST', '/api/user/alliance/add/111');
        $this->assertEquals(409, $response->getStatusCode());
    }

    public function testAdd503()
    {
        $this->setupDb();
        $this->loginUser(7);

        $this->client->setResponse(new Response(503));

        $response = $this->runApp(
            'POST',
            '/api/user/alliance/add/123',
            null,
            null,
            [
                HttpClientFactoryInterface::class => new HttpClientFactory($this->client),
                LoggerInterface::class => $this->log,
            ],
        );

        $this->assertEquals(503, $response->getStatusCode());
    }

    public function testAdd201()
    {
        $this->setupDb();
        $this->loginUser(7);

        $this->client->setResponse(new Response(200, [], '{
            "name": "The Alliance.",
            "ticker": "-AT-"
        }'));

        $response = $this->runApp(
            'POST',
            '/api/user/alliance/add/123456',
            null,
            null,
            [HttpClientFactoryInterface::class => new HttpClientFactory($this->client)],
        );

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertSame(
            ['id' => 123456, 'name' => 'The Alliance.', 'ticker' => '-AT-'],
            $this->parseJsonBody($response),
        );

        $this->em->clear();

        // check that alliance was created in db
        $alli = $this->alliRepo->find(123456);

        $this->assertSame(123456, $alli->getId());
        $this->assertSame('The Alliance.', $alli->getName());
        $this->assertSame('-AT-', $alli->getTicker());
    }

    public function testAddGroup403()
    {
        $this->setupDb();

        $response = $this->runApp('PUT', '/api/user/alliance/123/add-group/5');
        $this->assertEquals(403, $response->getStatusCode());

        $this->loginUser(6); # not a user-admin

        $response = $this->runApp('PUT', '/api/user/alliance/123/add-group/5');
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testAddGroup404()
    {
        $this->setupDb();
        $this->loginUser(7);

        $response1 = $this->runApp('PUT', '/api/user/alliance/' . $this->alliId1 . '/add-group/5');
        $response2 = $this->runApp('PUT', '/api/user/alliance/123/add-group/' . $this->groupId2);
        $response3 = $this->runApp('PUT', '/api/user/alliance/123/add-group/5');
        $this->assertEquals(404, $response1->getStatusCode());
        $this->assertEquals(404, $response2->getStatusCode());
        $this->assertEquals(404, $response3->getStatusCode());
    }

    public function testAddGroup204()
    {
        $this->setupDb();
        $this->loginUser(7);

        $response1 = $this->runApp('PUT', '/api/user/alliance/' . $this->alliId1 . '/add-group/' . $this->groupId2);
        $response2 = $this->runApp('PUT', '/api/user/alliance/' . $this->alliId1 . '/add-group/' . $this->groupId2);
        $this->assertEquals(204, $response1->getStatusCode());
        $this->assertEquals(204, $response2->getStatusCode());
    }

    public function testRemoveGroup403()
    {
        $this->setupDb();

        $response = $this->runApp('PUT', '/api/user/alliance/123/remove-group/5');
        $this->assertEquals(403, $response->getStatusCode());

        $this->loginUser(6); # not a user-admin

        $response = $this->runApp('PUT', '/api/user/alliance/123/remove-group/5');
        $this->assertEquals(403, $response->getStatusCode());
    }

    public function testRemoveGroup404()
    {
        $this->setupDb();
        $this->loginUser(7);

        $response1 = $this->runApp('PUT', '/api/user/alliance/' . $this->alliId1 . '/remove-group/5');
        $response2 = $this->runApp('PUT', '/api/user/alliance/123/remove-group/' . $this->groupId1);
        $response3 = $this->runApp('PUT', '/api/user/alliance/123/remove-group/5');
        $this->assertEquals(404, $response1->getStatusCode());
        $this->assertEquals(404, $response2->getStatusCode());
        $this->assertEquals(404, $response3->getStatusCode());
    }

    public function testRemoveGroup500()
    {
        $this->setupDb();
        $this->loginUser(7);

        $this->em->getEventManager()->addEventListener(Events::onFlush, self::$writeErrorListener);

        $res = $this->runApp(
            'PUT',
            '/api/user/alliance/' . $this->alliId1 . '/remove-group/' . $this->groupId1,
            null,
            null,
            [ObjectManager::class => $this->em, LoggerInterface::class => $this->log],
        );
        $this->assertEquals(500, $res->getStatusCode());
    }

    public function testRemoveGroup204()
    {
        $this->setupDb();
        $this->loginUser(7);

        $response1 = $this->runApp('PUT', '/api/user/alliance/' . $this->alliId1 . '/remove-group/' . $this->groupId1);
        $response2 = $this->runApp('PUT', '/api/user/alliance/' . $this->alliId1 . '/remove-group/' . $this->groupId1);
        $this->assertEquals(204, $response1->getStatusCode());
        $this->assertEquals(204, $response2->getStatusCode());
    }

    private function setupDb(): void
    {
        $this->helper->emptyDb();

        $this->helper->addCharacterMain('User', 6, [Role::USER]);
        $this->helper->addCharacterMain('Admin', 7, [Role::USER, Role::GROUP_ADMIN]);

        $alli1 = (new Alliance())->setId(111)->setTicker('t100')->setName('2 alli 1');
        $alli2 = (new Alliance())->setId(222)->setTicker('t200')->setName('1 alli 2');
        $alli3 = (new Alliance())->setId(333)->setTicker('t300')->setName('alli 3');

        $group1 = (new Group())->setName('group 1');
        $group2 = (new Group())->setName('group 2');

        $alli1->addGroup($group1);
        $alli2->addGroup($group1);
        $alli2->addGroup($group2);

        $this->em->persist($alli1);
        $this->em->persist($alli2);
        $this->em->persist($alli3);
        $this->em->persist($group1);
        $this->em->persist($group2);

        $this->em->flush();

        $this->alliId1 = $alli1->getId();

        $this->groupId1 = $group1->getId();
        $this->groupId2 = $group2->getId();
    }
}
