<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        // Les exports PDF fixent max_execution_time à 120 s : dans le processus unique de PHPUnit,
        // cette limite s'appliquerait à toute la suite. On la lève avant chaque test.
        set_time_limit(0);

        parent::setUp();
    }
}
