<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

use Illuminate\Foundation\Testing\DatabaseTransactions;

abstract class TestCase extends BaseTestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        if (!is_dir('/tmp/zapin_views')) {
            @mkdir('/tmp/zapin_views', 0777, true);
        }
        config(['view.compiled' => '/tmp/zapin_views']);
        $this->app->forgetInstance('blade.compiler');
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
    }
}
