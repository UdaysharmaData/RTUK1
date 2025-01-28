<?php

namespace Tests\Unit;

use Database\Factories\FaqCategoryFactory;
use Database\Factories\FaqFactory;
use Database\Seeders\TestPrepSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class FaqsTest extends TestCase
{
    use RefreshDatabase;
    /**
     * Test that FAQ can be created.
     *
     * @return void
     */
    public function test_that_faq_can_be_created()
    {
        $this->seed(TestPrepSeeder::class);

        FaqCategoryFactory::new()->create();
        $faq = FaqFactory::new()->create();

        $this->assertNotEmpty($faq);
    }

    /**
     * Test that FAQ category can be created.
     *
     * @return void
     */
    public function test_that_faq_category_can_be_created()
    {
        $this->seed(TestPrepSeeder::class);

        $category = FaqCategoryFactory::new()->create();

        $this->assertNotEmpty($category);
    }
}
