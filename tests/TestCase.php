<?php

namespace Tests;
use Illuminate\Support\Facades\Event;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        \Artisan::call('migrate', ['-vvv' => true]);
        \Artisan::call('passport:install', ['-vvv' => true]);
        \Artisan::call('db:seed', ['-vvv' => true]);
        Event::fake(); // Add this line to fake events
    }

    
}
