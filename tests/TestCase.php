<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (app()->environment('testing') && config('database.default') !== 'sqlite') {
            throw new \RuntimeException(
                'Unsafe test database configuration detected. Tests must run on sqlite, not '.config('database.default').'.'
            );
        }
    }
}
