<?php
/**
 * Created by PhpStorm.
 * User: james
 * Date: 11/1/18
 * Time: 8:54 AM
 */

namespace Piurafunk\LaravelScoutRedis\Engines;

use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Builder;
use Laravel\Scout\Engines\Engine;

class RedisEngine extends Engine
{
    /**
     * @var \Illuminate\Contracts\Redis\Factory
     */
    protected $redis;

    protected $prefix = 'redis-scout-engine.';

    public function __construct(RedisFactory $redis)
    {
        $this->redis = $redis;
    }

    /**
     * Update the given model in the index.
     *
     * @param  \Illuminate\Database\Eloquent\Collection $models
     * @return void
     */
    public function update($models)
    {
        $this->redis->pipeline(function ($pipe) use (&$models) {
            $models->each(function (Model &$model) use (&$pipe) {
                $this->redis->hset($this->prefix . get_class($model), $model->getKey(), $model->toJson());
            });
        });
    }

    /**
     * Remove the given model from the index.
     *
     * @param  \Illuminate\Database\Eloquent\Collection $models
     * @return void
     */
    public function delete($models)
    {
        $this->redis->pipeline(function ($pipe) use (&$models) {
            $models->each(function (Model $model) use (&$pipe) {
                $this->redis->hdel($this->prefix . get_class($model), $model->getKey());
            });
        });
    }

    /**
     * Perform the given search on the engine.
     *
     * @param  \Laravel\Scout\Builder $builder
     * @return mixed
     */
    public function search(Builder $builder)
    {
        // Grab all the results
        return $this->paginate($builder, PHP_INT_MAX, 0);
    }

    /**
     * Perform the given search on the engine.
     *
     * @param  \Laravel\Scout\Builder $builder
     * @param  int $perPage
     * @param  int $page
     * @return mixed
     */
    public function paginate(Builder $builder, $perPage, $page)
    {
        $skip = $perPage * ($page - 1);
        $skipped = 0;
        while ($skipped < $skip) {
            $this->performSearch($builder);
        }

        $results = [];

        foreach ($this->performSearch($builder) as $model) {
            $results[] = $model;

            if (count($results) == $perPage) {
                break;
            }
        }

        return [
            'hits' => $results,
            'class' => get_class($builder->model),
        ];
    }

    /**
     * Pluck and return the primary keys of the given results.
     *
     * @param  mixed $results
     * @return \Illuminate\Support\Collection
     */
    public function mapIds($results)
    {
        // Wrap the results in an array
        $hits = collect($results['hits']);

        // If the results are empty, return an empty collection
        if ($hits->count() == 0) {
            return collect();
        }

        // Determine they model's primary key name
        $class = '\\' . $results['class'];
        /** @var Model $model */
        $model = new $class;
        $keyName = $model->getKeyName();

        // Pluck the primary key for all results
        return $hits->pluck($keyName)->values();
    }

    /**
     * Map the given results to instances of the given model.
     *
     * @param  \Laravel\Scout\Builder $builder
     * @param  mixed $results
     * @param  \Illuminate\Database\Eloquent\Model $model
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function map(Builder $builder, $results, $model)
    {
        $collection = \Illuminate\Database\Eloquent\Collection::make();

        foreach ($results['hits'] as $result) {
            $collection[] = $model->newInstance($result, true);
        }

        return $collection;
    }

    /**
     * Get the total count from a raw result returned by the engine.
     *
     * @param  mixed $results
     * @return int
     */
    public function getTotalCount($results)
    {
        return count($results['hits']);
    }

    /**
     * Flush all of the model's records from the engine.
     *
     * @param  \Illuminate\Database\Eloquent\Model $model
     * @return void
     */
    public function flush($model)
    {
        $this->redis->hset($this->prefix . get_class($model), []);
    }

    /**
     * Get a valid result from the Redis cache, generator style
     *
     * @param Builder $builder
     * @return \Generator
     */
    protected function performSearch(Builder $builder)
    {
        $class = get_class($builder->model);

        // Get the keys from the cache
        $keys = collect($this->redis->hkeys($this->prefix . $class));

        foreach ($keys as $key) {
            $model = $this->redis->hget($this->prefix . $class, $key);

            if ($this->validResult($model, $builder->query, $builder->wheres)) {
                $model = json_decode($model, true);
                $model['class'] = get_class($builder->model);
                yield $model;
            }
        }
    }

    /**
     * Determine if the cached model is meets criteria
     *
     * @param string $model
     * @param string $query
     * @param array $wheres
     * @return bool
     */
    protected function validResult($model, $query, $wheres)
    {
        // Wrap the wheres in a collection
        $wheres = collect($wheres);

        // Decode the model into an array
        $array = json_decode($model, true);

        // Determine whether this result matches the given criteria
        return str_contains($model, $query) && $wheres->every(function ($value, $key) use ($array) {
                return array_key_exists($key, $array) && $array[$key] == $value;
            });
    }
}