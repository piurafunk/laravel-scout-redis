<?php
/**
 * Created by PhpStorm.
 * User: james
 * Date: 11/1/18
 * Time: 12:50 PM
 */

namespace Test\Heavy;

use Faker\Factory as Faker;
use Illuminate\Redis\Connections\PredisConnection;
use Laravel\Scout\Builder;
use Piurafunk\LaravelScoutRedis\Engines\RedisEngine;
use Predis\Client;
use Test\Models\BasicModel;
use Test\TestCase;

class TestResponseTime extends TestCase
{
    /**
     * @var RedisEngine
     */
    protected $engine;

    /**
     * @var PredisConnection
     */
    protected $redis;

    /**
     * @var \Faker\Generator
     */
    protected $faker;

    /**
     * @var string
     */
    protected $prefix = 'redis-scout-engine.';

    public function setUp()
    {
        ini_set('memory_limit', '1G');

        parent::setUp();

        $this->faker = Faker::create();

        $this->redis = New PredisConnection(new Client([
            'scheme' => 'tcp',
            'host' => 'redis',
            'port' => 6379,
        ]));

        $this->engine = new RedisEngine($this->redis);
    }

    protected function tearDown()
    {
        ini_set('memory_limit', '128M');

        parent::tearDown();

        $this->redis->flushall();
    }

    public function testOneHundredThousandEntries() {
        $class = BasicModel::class;

        for ($i = 0; $i < 100000; $i++) {
            $model = json_encode(['id' => $i, 'word' => $this->faker->sentence]);

            $this->redis->hset($this->prefix . $class, $i, $model);
        }

        $start = microtime(true);

        $this->engine->search((new Builder(new BasicModel, 'lorem')));

        $diff = microtime(true) - $start;

        $this->assertLessThan(5, $diff);
    }
}
