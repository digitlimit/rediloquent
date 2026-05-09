<?php

use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Pest configuration
|--------------------------------------------------------------------------
|
| This file bootstraps Pest. All tests under tests/ use the TestCase base
| class so they have access to the fully booted Laravel application (via
| Orchestra Testbench) and a clean Redis database for every test.
|
*/

uses(TestCase::class)->in('Feature');
