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

    public function test_guests_can_read_ratings_but_cannot_submit_them(): void
    {
        $product = $this->product();
        $this->get(route('products.show', $product))->assertOk()
            ->assertSee('Belum ada rating')->assertSee('Masuk untuk memberi rating');
        $this->post(route('products.rating', $product), ['score' => 5])
            ->assertRedirect(route('login'));
        $this->assertDatabaseCount('product_ratings', 0);
    }

    public function test_rating_is_owned_by_authenticated_user_and_updates_instead_of_duplicating(): void
    {
        $product = $this->product();
        $user = User::factory()->create();
        $other = User::factory()->create();
        $product->ratings()->create(['user_id' => $other->id, 'score' => 1]);

        $this->actingAs($user)->post(route('products.rating', $product), [
            'score' => 5, 'user_id' => $other->id,
        ])->assertSessionHasNoErrors()->assertRedirect(route('products.show', $product).'#rating');
        $this->post(route('products.rating', $product), ['score' => 4])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('product_ratings', 2);
        $this->assertDatabaseHas('product_ratings', ['product_id' => $product->id, 'user_id' => $user->id, 'score' => 4]);
        $this->assertDatabaseHas('product_ratings', ['product_id' => $product->id, 'user_id' => $other->id, 'score' => 1]);
        $this->get(route('products.show', $product))->assertOk()->assertSee('Perbarui rating');
    }

    public function test_invalid_scores_are_rejected_with_visible_errors(): void
    {
        $product = $this->product();
        $this->actingAs(User::factory()->create());

        foreach ([null, 0, 6, -1, 2.5, 'invalid', [5]] as $score) {
            $response = $this->from(route('products.show', $product))
                ->post(route('products.rating', $product), ['score' => $score])
                ->assertRedirect(route('products.show', $product))
                ->assertSessionHasErrors(['score'], null, 'rating');

            $this->assertNotEmpty($response->getSession()->get('errors')->getBag('rating')->get('score'));
        }

        $this->assertDatabaseCount('product_ratings', 0);
    }

    public function test_inactive_and_missing_products_cannot_be_rated(): void
    {
        $product = $this->product();
        $product->update(['is_active' => false]);
        $this->actingAs(User::factory()->create());
        $this->post(route('products.rating', $product), ['score' => 5])->assertNotFound();
        $this->post('/produk/999999/rating', ['score' => 5])->assertNotFound();
        $this->assertDatabaseCount('product_ratings', 0);
    }

    public function test_real_averages_counts_and_footer_links_render_on_public_pages(): void
    {
        $product = $this->product();
        $this->get('/')->assertOk()->assertSee('Belum ada rating.');
        foreach ([1, 5] as $score) {
            $product->ratings()->create(['user_id' => User::factory()->create()->id, 'score' => $score]);
        }

        foreach (['/', route('catalog'), route('products.show', $product)] as $url) {
            $this->get($url)->assertOk()->assertSee('3,0 / 5')->assertSee('2 penilaian')
                ->assertSee('https://www.instagram.com/j2m_tangsel/', false)
                ->assertSee('https://s.shopee.co.id/2gCekvp5Nb?share_channel_code=1', false)
                ->assertSee('target="_blank" rel="noopener noreferrer"', false);
        }

        $product->update(['is_active' => false]);
        $this->get('/')->assertOk()->assertSee('Belum ada rating.')->assertDontSee('3,0 / 5');
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
