<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductRating;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductRatingTest extends TestCase
{
    use RefreshDatabase;

    private function product(): Product
    {
        $category = Category::create(['name' => 'Bahan jok']);

        return Product::create([
            'category_id' => $category->id,
            'name' => 'Busa',
            'description' => 'Bahan jok untuk simulasi.',
            'price' => 100000,
            'stock' => 10,
            'is_active' => true,
        ]);
    }

    public function test_rating_routes_return_404(): void
    {
        $product = $this->product();
        $user = User::factory()->create();

        // Guest POST to product rating returns 404
        $this->post('/produk/'.$product->id.'/rating', ['score' => 5])
            ->assertNotFound();

        // Authenticated POST to product rating returns 404
        $this->actingAs($user)
            ->post('/produk/'.$product->id.'/rating', ['score' => 5])
            ->assertNotFound();

        // Non-existent rating pages return 404
        $this->get('/rating')->assertNotFound();
        $this->get('/page')->assertNotFound();
    }

    public function test_public_pages_do_not_contain_rating_features_or_links(): void
    {
        $product = $this->product();

        // Homepage does not show rating sections
        $this->get('/')
            ->assertOk()
            ->assertDontSee('PENILAIAN PENGGUNA')
            ->assertDontSee('Rating Produk J2M')
            ->assertDontSee('Beri rating')
            ->assertDontSee('rating-overview');

        // Catalog does not show rating summaries or rating links
        $this->get(route('catalog'))
            ->assertOk()
            ->assertDontSee('rating-summary')
            ->assertDontSee('penilaian');

        // Product detail does not show rating form, rating options, or rating links
        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertDontSee('Beri rating produk')
            ->assertDontSee('Masuk untuk memberi rating')
            ->assertDontSee('Perbarui rating')
            ->assertDontSee('#rating')
            ->assertDontSee('rating-fieldset');

        // Authenticated user also does not see rating form on product detail
        $this->actingAs(User::factory()->create())
            ->get(route('products.show', $product))
            ->assertOk()
            ->assertDontSee('Beri rating produk')
            ->assertDontSee('Kirim rating')
            ->assertDontSee('#rating');
    }

    public function test_removing_user_or_product_cleans_up_ratings(): void
    {
        $product = $this->product();
        $user = User::factory()->create();
        $product->ratings()->create(['user_id' => $user->id, 'score' => 5]);
        $user->delete();
        $this->assertDatabaseCount('product_ratings', 0);

        $product->ratings()->create(['user_id' => User::factory()->create()->id, 'score' => 3]);
        $product->delete();
        $this->assertSame(0, ProductRating::count());
    }
}
