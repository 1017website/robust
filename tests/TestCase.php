<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Production reports use MySQL MONTH(); support the same query in isolated SQLite tests.
        $connection = $this->app['db']->connection();
        if ($connection->getDriverName() !== 'sqlite' || $connection->getDatabaseName() !== ':memory:') {
            throw new \RuntimeException('Test dihentikan: database test wajib SQLite in-memory agar data aplikasi tidak terhapus.');
        }

        $connection->getPdo()->sqliteCreateFunction('MONTH', fn ($date) => $date ? (int) date('n', strtotime($date)) : null, 1);
    }
}
