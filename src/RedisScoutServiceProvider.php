<?php
/**
 * Created by PhpStorm.
 * User: james
 * Date: 11/1/18
 * Time: 8:55 AM
 */

namespace Piurafunk\LaravelScoutRedis;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;
use Laravel\Scout\EngineManager;
use Piurafunk\LaravelScoutRedis\Engines\RedisEngine;

class RedisScoutServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->make(EngineManager::class)->extend('redis', function (Container $app) {
            return $app->make(RedisEngine::class);
        });
    }
}