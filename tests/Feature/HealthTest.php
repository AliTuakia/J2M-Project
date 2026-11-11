<?php

namespace Tests\Feature;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class HealthTest extends TestCase
{
    public function test_health_is_json_without_database_or_session_dependencies(): void
    {
        config(['session.driver' => 'database', 'cache.default' => 'database']);
        // The test response factory resolves a session handler even without web
        // middleware. Allow a connection object, but fail on any actual query.
        $this->app['db']->extend('health-test', fn () => new Connection(function () {
            throw new \RuntimeException('Health must not connect to a database.');
        }));
        config(['database.default' => 'health-test', 'database.connections.health-test' => ['driver' => 'health-test']]);

        $this->get('/health')->assertOk()->assertExactJson(['status' => 'ok'])
            ->assertHeader('Content-Type', 'application/json');
        $this->assertSame([], Route::getRoutes()->getByName('health')->gatherMiddleware());
    }

    public function test_framework_health_route_is_preserved(): void
    {
        $this->get('/up')->assertOk();
    }
}
