<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware\Guzzle;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Neucore\Data\EsiErrorLimit;
use Neucore\Factory\RepositoryFactory;
use Neucore\Middleware\Guzzle\EsiHeaders;
use Neucore\Service\ObjectManager;
use Neucore\Storage\Variables;
use Neucore\Storage\SystemVariableStorage;
use PHPUnit\Framework\TestCase;
use Tests\Helper;
use Tests\Logger;

class EsiHeadersTest extends TestCase
{
    private Helper $helper;

    private Logger $logger;

    private SystemVariableStorage $storage;

    private EsiHeaders $obj;

    protected function setUp(): void
    {
        $this->helper = new Helper();
        $this->helper->emptyDb();
        $om = $this->helper->getObjectManager();

        $this->logger = new Logger();

        $this->storage = new SystemVariableStorage(new RepositoryFactory($om), new ObjectManager($om, $this->logger));
        #apcu_clear_cache();
        #$this->storage = new \Neucore\Storage\ApcuStorage();

        $this->obj = new EsiHeaders($this->logger, $this->storage);
    }

    public function testInvokeErrorLimit()
    {
        $response = new Response(
            200,
            ['X-Esi-Error-Limit-Remain' => ['100'], 'X-Esi-Error-Limit-Reset' => ['60']],
        );

        $function = $this->obj->__invoke($this->helper->getGuzzleHandler($response));
        $function(new Request('GET', 'https://local.host/esi/path'), []);

        $val = EsiErrorLimit::fromJson((string) $this->storage->get(Variables::ESI_ERROR_LIMIT));

        $this->assertSame(100, $val->remain);
        $this->assertSame(60, $val->reset);
        $this->assertLessThanOrEqual(time(), $val->updated);
    }

    public function testInvokeDeprecated()
    {
        $response = new Response(
            200,
            ['warning' => ['299 - This route is deprecated'], 'Warning' => ['299 - This route is deprecated']],
        );

        $function = $this->obj->__invoke($this->helper->getGuzzleHandler($response));
        $function(new Request('GET', 'https://local.host/esi/path'), []);

        $this->assertSame(2, count($this->logger->getHandler()->getRecords()));
        $this->assertSame(
            'https://local.host/esi/path: 299 - This route is deprecated',
            $this->logger->getHandler()->getRecords()[0]['message'],
        );
        $this->assertSame(
            'https://local.host/esi/path: 299 - This route is deprecated',
            $this->logger->getHandler()->getRecords()[1]['message'],
        );
    }
}
