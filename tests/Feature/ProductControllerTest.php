<?php
namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\SupportedCurrency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a user and authenticate as that user
        $user = User::factory()->create();
        $this->actingAs($user, 'api');
    }

    /** @test */
    public function can_create_product()
    {
        $brand = ProductBrand::factory()->create();
        
        // Make sure to use a valid currency code from your supported currencies
        $supportedCurrency = SupportedCurrency::factory()->create(['code' => 'USD']);

        $data = [
            'title'       => $this->faker->sentence,
            'label'       => $this->faker->word,
            'description' => $this->faker->paragraph,
            'instruction' => $this->faker->paragraph,
            'value'       => $this->faker->randomFloat(2, 1, 100),
            'currency'    => $supportedCurrency->code,
            'brand_id'    => $brand->id,
        ];

        $response = $this->postJson(route('product.admin.create'), $data);

        $response->assertStatus(200);

        $this->assertDatabaseHas('products', ['title' => $data['title']]);
    }


}
