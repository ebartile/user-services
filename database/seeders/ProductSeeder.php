<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductBrand;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (app()->environment('local')) {
            ProductBrand::factory()
                ->has($this->productsFactory(), 'products')
                ->count(2)->create();
        }
    }

    /**
     * Product
     *
     * @return Factory
     */
    protected function productsFactory(): Factory
    {
        return Product::factory()
            ->count(200);
    }

}
