<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // الاختبارات لا تحتاج ملفات CSS/JS المبنية، فتعمل على أي جهاز أو CI بدون npm run build
        $this->withoutVite();
    }
}
