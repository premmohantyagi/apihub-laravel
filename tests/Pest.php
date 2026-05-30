<?php

use ApiHub\Laravel\Tests\TestCase;

// Feature tests boot a Testbench application; unit tests run standalone.
uses(TestCase::class)->in('Feature');
