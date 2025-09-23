<?php

namespace Tests;

use Tests\Support\TestCase;
use Reactmore\QiosPay\Services\Products;

final class ServiceTest extends TestCase
{
    public function testProductsServiceInstance(): void
    {
        $products = $this->qiospay->products();
        $this->assertInstanceOf(Products::class, $products);
    }
}
