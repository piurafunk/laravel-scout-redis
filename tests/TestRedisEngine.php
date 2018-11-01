<?php
/**
 * Created by PhpStorm.
 * User: james
 * Date: 11/1/18
 * Time: 9:20 AM
 */

namespace Test;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Redis\Connections\PredisConnection;
use Laravel\Scout\Builder;
use Mockery;
use Piurafunk\LaravelScoutRedis\Engines\RedisEngine;
use Test\Models\BasicModel;

class TestRedisEngine extends TestCase
{
    public function testUpdateEntry() {
        /** @var PredisConnection|Mockery\Mock $redis */
        $redis = Mockery::mock(PredisConnection::class);

        $model = new BasicModel;
        $model->id = 1;

        $redis->shouldReceive('pipeline');
        $redis->shouldReceive('hset')
            ->with('redis-scout-engine.Test\\Models\\BasicModel', 1, $model->toJson());

        $engine = new RedisEngine($redis);

        $engine->update(Collection::make([$model]));
    }

    public function testDeleteEntry() {
        /** @var PredisConnection|Mockery\Mock $redis */
        $redis = Mockery::mock(PredisConnection::class);

        $model = new BasicModel;
        $model->id = 1;

        $redis->shouldReceive('pipeline');
        $redis->shouldReceive('hdel')
            ->with('redis-scout-engine.Test\\Models\\BasicModel', 1);

        $engine = new RedisEngine($redis);

        $engine->delete(Collection::make([$model]));
    }

    public function testSearch() {
        /** @var PredisConnection|Mockery\Mock $redis */
        $redis = Mockery::mock(PredisConnection::class);

        $model = new BasicModel;
        $model->word = 'yodles';
        $model->id = 1;

        $redis->shouldReceive('hkeys')
            ->with('redis-scout-engine.Test\\Models\\BasicModel')
            ->andReturn([1]);
        $redis->shouldReceive('hget')
            ->with('redis-scout-engine.Test\\Models\\BasicModel', 1)
            ->andReturn($model->toJson());

        $engine = new RedisEngine($redis);

        $builder = (new Builder($model, 'yodles'))->where('word', 'yodles');

        $engine->search($builder);
    }

    public function testMapIds() {
        /** @var PredisConnection|Mockery\Mock $redis */
        $redis = Mockery::mock(PredisConnection::class);

        $model = new BasicModel;
        $model->word = 'yodles';
        $model->id = 1;

        $redis->shouldReceive('hkeys')
            ->with('redis-scout-engine.Test\\Models\\BasicModel')
            ->andReturn([1]);
        $redis->shouldReceive('hget')
            ->with('redis-scout-engine.Test\\Models\\BasicModel', 1)
            ->andReturn($model->toJson());

        $engine = new RedisEngine($redis);

        $builder = (new Builder($model, 'yodles'))->where('word', 'yodles');

        $results = $engine->search($builder);

        $ids = $engine->mapIds($results);

        $this->assertCount(1, $ids);
        $this->assertEquals(1, $ids->first());
    }

    public function testMap() {
        /** @var PredisConnection|Mockery\Mock $redis */
        $redis = Mockery::mock(PredisConnection::class);

        $model = new BasicModel;
        $model->word = 'yodles';
        $model->id = 1;

        $redis->shouldReceive('hkeys')
            ->with('redis-scout-engine.Test\\Models\\BasicModel')
            ->andReturn([1]);
        $redis->shouldReceive('hget')
            ->with('redis-scout-engine.Test\\Models\\BasicModel', 1)
            ->andReturn($model->toJson());

        $engine = new RedisEngine($redis);

        $builder = (new Builder($model, 'yodles'))->where('word', 'yodles');

        $results = $engine->search($builder);

        $models = $engine->map($builder, $results, $model);

        $this->assertCount(1, $models);
        $this->assertEquals($model->getFillable(), $models->first()->getFillable());
    }

    public function testGetTotalCount() {
        /** @var PredisConnection|Mockery\Mock $redis */
        $redis = Mockery::mock(PredisConnection::class);

        $model = new BasicModel;
        $model->word = 'yodles';
        $model->id = 1;

        $redis->shouldReceive('hkeys')
            ->with('redis-scout-engine.Test\\Models\\BasicModel')
            ->andReturn([1]);
        $redis->shouldReceive('hget')
            ->with('redis-scout-engine.Test\\Models\\BasicModel', 1)
            ->andReturn($model->toJson());

        $engine = new RedisEngine($redis);

        $builder = (new Builder($model, 'yodles'))->where('word', 'yodles');

        $results = $engine->search($builder);

        $count = $engine->getTotalCount($results);

        $this->assertEquals(1, $count);
    }

    public function testFlush() {
        /** @var PredisConnection|Mockery\Mock $redis */
        $redis = Mockery::mock(PredisConnection::class);

        $model = new BasicModel;
        $model->word = 'yodles';
        $model->id = 1;

        $redis->shouldReceive('del')
            ->with(['redis-scout-engine.Test\\Models\\BasicModel']);

        $engine = new RedisEngine($redis);

        $builder = (new Builder($model, 'yodles'))->where('word', 'yodles');

        $engine->flush($builder->model);
    }
}