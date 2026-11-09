<?php

namespace Tests\Unit;

use App\Models\Product;
use PHPUnit\Framework\TestCase;

class ProductTest extends TestCase
{
    public function test_custom_product_illustration_ignores_case_and_surrounding_spaces(): void
    {
        $this->assertTrue((new Product(['name' => '  SATU SET JOK CUSTOM  ']))->usesIllustration());
        $this->assertFalse((new Product(['name' => 'Busa']))->usesIllustration());
        $this->assertFalse((new Product)->usesIllustration());
    }
}
