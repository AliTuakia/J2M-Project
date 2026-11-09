<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CheckoutBoundaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_without_review_or_with_changed_cart_is_rejected(): void
    {
        config(['demo.admin_email' => null, 'demo.admin_password' => null]);
        $this->seed(DatabaseSeeder::class);
        $user = User::factory()->create();
        $product = Product::where('requires_custom', false)->firstOrFail();
        $this->actingAs($user)->get(route('checkout.create'))->assertRedirect(route('cart.index'));
        $this->post(route('cart.store', $product), ['quantity' => 1])->assertSessionHasNoErrors();
        $data = ['checkout_token' => (string) Str::uuid(), 'recipient_name' => 'Demo', 'address' => 'Alamat fiktif demo kuliah', 'shipping_method' => 'simulation', 'payment_method' => 'simulation', 'simulation_agreement' => '1'];
        $this->post(route('checkout.store'), $data)->assertRedirect(route('checkout.create'))->assertSessionHasErrors('checkout');
        $this->get(route('checkout.create'))->assertOk();
        $data['checkout_token'] = session('checkout_review.token');
        $item = $user->cart->items()->first();
        $this->patch(route('cart.update', $item->id), ['quantity' => 2])->assertSessionHasNoErrors();
        $this->post(route('checkout.store'), $data)->assertSessionHasErrors('cart');
        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(10, $product->fresh()->stock);
    }

    public function test_mutating_routes_reject_requests_without_csrf_token(): void
    {
        // Laravel bypasses CSRF in testing; change only the application environment for this assertion.
        $this->app['env'] = 'local';
        $this->post(route('register'), ['name' => 'Demo'])->assertStatus(419);
        $this->assertDatabaseCount('users', 0);
    }
}
