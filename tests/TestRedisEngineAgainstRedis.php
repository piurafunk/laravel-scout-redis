<?php
/**
 * Created by PhpStorm.
 * User: james
 * Date: 11/1/18
 * Time: 11:44 AM
 */

namespace Test;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Redis\Connections\PredisConnection;
use Laravel\Scout\Builder;
use Piurafunk\LaravelScoutRedis\Engines\RedisEngine;
use Predis\Client;
use Test\Models\BasicModel;

class TestRedisEngineAgainstRedis extends TestCase
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
     * @var string
     */
    protected $prefix = 'redis-scout-engine.';

    protected function setUp()
    {
        parent::setUp();

        $this->redis = New PredisConnection(new Client([
            'scheme' => 'tcp',
            'host' => 'redis',
            'port' => 6379,
        ]));

        $this->engine = new RedisEngine($this->redis);
    }

    protected function tearDown()
    {
        parent::tearDown();

        $this->redis->flushall();
    }

    public function dataProvider() {
        return [
            [['id' => 1]],
            [['id' => 2, 'word' => 'yodles']],
            [['id' => 3, 'name' => 'James Funk']],
        ];
    }

    /**
     * @dataProvider dataProvider
     * @param $data
     */
    public function testUpdateEntry($data)
    {
        $model = new BasicModel;
        $model->forceFill($data);

        $this->engine->update(Collection::make([$model]));

        $redisModel = $this->redis->hget($this->prefix . BasicModel::class, $model->getKey());

        $this->assertEquals($redisModel, $model->toJson());
    }

    /**
     * @dataProvider dataProvider
     * @param $data
     */
    public function testDeleteEntry($data)
    {
        $model = new BasicModel;
        $model->forceFill($data);

        $this->redis->hset($this->prefix . BasicModel::class, $model->getKey(), $model->toJson());

        $this->engine->delete(Collection::make([$model]));

        $redisModel = $this->redis->hget($this->prefix . BasicModel::class, $model->getKey());

        $this->assertNull($redisModel);
    }

    public function testSearch()
    {
        $model = new BasicModel;
        $model->forceFill(['id' => 3, 'name' => 'James Funk']);

        $this->redis->hset($this->prefix . BasicModel::class, $model->getKey(), $model->toJson());

        $builder = (new Builder($model, 'James Funk'));

        $hits = $this->engine->search($builder)['hits'];

        $this->assertCount(1, $hits);
        $this->assertEquals($model->toArray(), $hits[0]);
    }

    public function testMapIds()
    {
        $model = new BasicModel;
        $model->forceFill(['id' => 3, 'name' => 'James Funk']);

        $this->redis->hset($this->prefix . BasicModel::class, $model->getKey(), $model->toJson());

        $builder = (new Builder($model, 'James Funk'));

        $results = $this->engine->search($builder);

        $ids = $this->engine->mapIds($results);

        $this->assertCount(1, $ids);
        $this->assertEquals(3, $ids->first());
    }

    public function testMap()
    {
        $model = new BasicModel;
        $model->forceFill(['id' => 3, 'name' => 'James Funk']);

        $this->redis->hset($this->prefix . BasicModel::class, $model->getKey(), $model->toJson());

        $builder = (new Builder($model, 'James Funk'));

        $results = $this->engine->search($builder);

        $models = $this->engine->map($builder, $results, $model);

        $this->assertCount(1, $models);
        $this->assertInstanceOf(BasicModel::class, $models->first());
        $this->assertEquals($model->getFillable(), $models->first()->getFillable());
    }

    public function testGetTotalCount()
    {
        $model = new BasicModel;
        $model->forceFill(['id' => 3, 'name' => 'James Funk']);

        $this->redis->hset($this->prefix . BasicModel::class, $model->getKey(), $model->toJson());

        $builder = (new Builder($model, 'James Funk'));

        $results = $this->engine->search($builder);

        $count = $this->engine->getTotalCount($results);

        $this->assertEquals(1, $count);
    }

    public function testFlush()
    {
        $model = new BasicModel;
        $model->forceFill(['id' => 3, 'name' => 'James Funk']);

        $this->redis->hset($this->prefix . BasicModel::class, $model->getKey(), $model->toJson());

        $builder = (new Builder($model, 'James Funk'));

        $this->engine->flush($builder->model);

        $redisModel = $this->redis->hget($this->prefix . BasicModel::class, $model->getKey());

        $this->assertNull($redisModel);
    }
}
