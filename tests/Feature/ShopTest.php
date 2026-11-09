<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ShopTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['demo.admin_email' => null, 'demo.admin_password' => null]);
        $this->seed(DatabaseSeeder::class);
    }

    private function customer(): User
    {
        return User::factory()->create();
    }

    private function admin(): User
    {
        $admin = $this->customer();
        $admin->role = 'admin';
        $admin->save();

        return $admin;
    }

    private function product(bool $custom = false): Product
    {
        return Product::where('requires_custom', $custom)->firstOrFail();
    }

    private function add(Product $product, int $quantity = 1, array $custom = []): void
    {
        $this->post(route('cart.store', $product), ['quantity' => $quantity] + $custom)
            ->assertSessionHasNoErrors()->assertRedirect(route('cart.index'));
    }

    private function review(): array
    {
        $this->get(route('checkout.create'))->assertOk();

        return [
            'checkout_token' => session('checkout_review.token'),
            'recipient_name' => 'Pelanggan Demo',
            'address' => 'Alamat fiktif untuk demo kuliah saja',
            'shipping_method' => 'simulation', 'payment_method' => 'simulation',
            'simulation_agreement' => '1',
        ];
    }

    public function test_public_catalog_search_filter_and_guest_cart_guard(): void
    {
        $product = $this->product();
        $this->get('/')->assertOk()->assertSee('simulasi');
        $this->get(route('catalog', ['q' => 'Kulit jok']))->assertOk()->assertSee('Kulit jok biasa')->assertDontSee('Satu set jok custom');
        $this->get(route('catalog', ['category' => $this->product(true)->category_id]))->assertOk()->assertSee('Satu set jok custom')->assertDontSee('Kulit jok biasa');
        $this->get(route('products.show', $product))->assertOk();
        $this->post(route('cart.store', $product), ['quantity' => 1])->assertRedirect(route('login'));
        $this->get(route('products.login', $product))->assertRedirect(route('login'));
        $this->assertSame(route('products.show', $product), session('url.intended'));
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_registration_cannot_promote_role_and_login_logout_work(): void
    {
        $this->post(route('register'), [
            'name' => 'Demo', 'email' => 'demo@example.test', 'password' => 'demo-password-123',
            'password_confirmation' => 'demo-password-123', 'role' => 'admin',
        ])->assertSessionHasNoErrors();
        $user = User::where('email', 'demo@example.test')->firstOrFail();
        $this->assertSame('customer', $user->role);
        $this->assertTrue(Hash::check('demo-password-123', $user->password));
        $this->assertAuthenticatedAs($user);
        $this->get(route('admin.dashboard'))->assertForbidden();
        $this->post(route('logout'))->assertRedirect(route('home'));
        $this->assertGuest();
        $this->post(route('login'), ['email' => $user->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->post(route('login'), ['email' => $user->email, 'password' => 'demo-password-123'])->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_attempts_are_limited(): void
    {
        $user = $this->customer();
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login'), ['email' => $user->email, 'password' => 'invalid'])->assertSessionHasErrors('email');
        }
        $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors(['email' => 'Terlalu banyak percobaan. Silakan coba kembali satu menit lagi.']);
        $this->assertGuest();
    }

    public function test_cart_quantity_validation_custom_requirements_and_ownership(): void
    {
        $user = $this->customer();
        $this->actingAs($user);
        $product = $this->product(true);
        $this->post(route('cart.store', $product), ['quantity' => 1])->assertSessionHasErrors('motorcycle_type');
        foreach ([0, -1, 'abc', 11] as $quantity) {
            $this->post(route('cart.store', $product), ['quantity' => $quantity, 'motorcycle_type' => 'Motor demo'])->assertSessionHasErrors('quantity');
        }
        $this->add($product, 1, ['motorcycle_type' => 'Motor A', 'request_notes' => 'Request A']);
        $this->add($product, 1, ['motorcycle_type' => 'Motor B', 'request_notes' => 'Request B']);
        $this->assertDatabaseCount('cart_items', 2);
        $item = $user->cart->items()->first();
        $this->patch(route('cart.update', $item->id), ['quantity' => 3])->assertSessionHasNoErrors();
        $this->patch(route('cart.update', $item->id), ['quantity' => 10])->assertSessionHasErrors('quantity');
        $this->get(route('cart.index'))->assertOk()->assertSee('Request A');
        $this->actingAs($this->customer());
        $this->get(route('cart.index'))->assertOk();
        $this->delete(route('cart.destroy', $item->id))->assertNotFound();
        $this->patch(route('cart.update', $item->id), ['quantity' => 1])->assertNotFound();
        $this->actingAs($user)->delete(route('cart.destroy', $item->id))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('cart_items', 1);
    }

    public function test_checkout_snapshots_custom_data_ignores_client_total_and_is_idempotent(): void
    {
        $user = $this->customer();
        $this->actingAs($user);
        $product = $this->product(true);
        $this->add($product, 2, ['motorcycle_type' => 'Motor Demo', 'request_notes' => '<script>alert(1)</script>']);
        $data = $this->review();
        $this->post(route('checkout.store'), $data + ['total' => 1, 'price' => 1, 'shipping_cost' => 999])
            ->assertSessionHasNoErrors();
        $order = Order::firstOrFail();
        $this->assertSame(600000, (int) $order->total);
        $this->assertSame(8, $product->fresh()->stock);
        $this->assertSame('Motor Demo', $order->items->first()->motorcycle_type);
        $this->assertDatabaseCount('cart_items', 0);
        $this->post(route('checkout.store'), $data)->assertRedirect(route('orders.show', $order));
        $this->assertDatabaseCount('orders', 1);
        $this->assertSame(8, $product->fresh()->stock);
        $product->update(['name' => 'Nama baru', 'price' => 900000, 'is_active' => false]);
        $this->get(route('orders.show', $order))->assertOk()->assertSee('Satu set jok custom')->assertSee('Rp300.000')->assertDontSee('<script>alert(1)</script>', false);
        $this->get(route('orders.index'))->assertOk()->assertSee($order->number);
        $this->get(route('products.show', $product))->assertNotFound();
        $this->actingAs($this->customer())->get(route('orders.show', $order))->assertNotFound();
    }

    public function test_checkout_rejects_price_changes_then_accepts_new_review(): void
    {
        $this->actingAs($this->customer());
        $product = $this->product();
        $this->add($product);
        $data = $this->review();
        $product->update(['price' => 55000]);
        $this->from(route('checkout.create'))->post(route('checkout.store'), $data)->assertSessionHasErrors('cart');
        $this->assertDatabaseCount('orders', 0);
        $data = $this->review();
        $this->post(route('checkout.store'), $data)->assertSessionHasNoErrors();
        $this->assertSame(55000, (int) Order::firstOrFail()->total);
    }

    public function test_checkout_stock_failure_rolls_back_all_items(): void
    {
        $this->actingAs($this->customer());
        $normal = $this->product();
        $custom = $this->product(true);
        $this->add($normal, 2);
        $this->add($custom, 1, ['motorcycle_type' => 'Motor Demo']);
        $data = $this->review();
        $custom->update(['stock' => 0]);
        $this->post(route('checkout.store'), $data)->assertSessionHasErrors('stock');
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertDatabaseCount('cart_items', 2);
        $this->assertSame(10, $normal->fresh()->stock);
    }

    public function test_last_unit_cannot_be_purchased_by_second_customer(): void
    {
        $product = $this->product();
        $product->update(['stock' => 1]);
        $a = $this->customer();
        $b = $this->customer();
        $this->actingAs($a);
        $this->add($product);
        $this->actingAs($b);
        $this->add($product);
        $this->actingAs($a);
        $data = $this->review();
        $this->post(route('checkout.store'), $data)->assertSessionHasNoErrors();
        $this->actingAs($b);
        $data = $this->review();
        $this->post(route('checkout.store'), $data)->assertSessionHasErrors('stock');
        $this->assertDatabaseCount('orders', 1);
        $this->assertSame(0, $product->fresh()->stock);
    }

    public function test_checkout_rejects_inactive_product_and_invalid_simulation_input(): void
    {
        $this->actingAs($this->customer());
        $product = $this->product();
        $this->add($product);
        $data = $this->review();
        $this->post(route('checkout.store'), array_replace($data, ['payment_method' => 'real', 'simulation_agreement' => '0']))
            ->assertSessionHasErrors(['payment_method', 'simulation_agreement']);
        $product->update(['is_active' => false]);
        $this->post(route('checkout.store'), $data)->assertSessionHasErrors('stock');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_admin_pages_product_management_categories_and_status_flow(): void
    {
        $customer = $this->customer();
        $this->actingAs($customer);
        $this->add($this->product());
        $this->post(route('checkout.store'), $this->review())->assertSessionHasNoErrors();
        $order = Order::firstOrFail();
        $this->patch(route('admin.orders.update', $order), ['status' => 'processing'])->assertForbidden();
        $this->actingAs($this->admin());
        foreach (['admin.dashboard', 'admin.products', 'admin.products.create', 'admin.categories', 'admin.orders'] as $route) {
            $this->get(route($route))->assertOk();
        }
        $this->get(route('admin.orders.show', $order))->assertOk();
        $this->patch(route('admin.orders.update', $order), ['status' => 'completed'])->assertSessionHasErrors('status');
        $this->patch(route('admin.orders.update', $order), ['status' => 'processing'])->assertSessionHasNoErrors();
        $this->patch(route('admin.orders.update', $order), ['status' => 'completed'])->assertSessionHasNoErrors();
        $this->patch(route('admin.orders.update', $order), ['status' => 'processing'])->assertSessionHasErrors('status');
        $this->post(route('admin.categories.store'), ['name' => 'Kategori demo baru'])->assertSessionHasNoErrors();
        $category = Category::where('name', 'Kategori demo baru')->firstOrFail();
        $this->put(route('admin.categories.update', $category), ['name' => 'Kategori diperbarui'])->assertSessionHasNoErrors();
        $attributes = ['name' => 'Produk contoh', 'category_id' => $category->id, 'description' => 'Deskripsi contoh', 'price' => 12000, 'stock' => 3, 'is_active' => '1', 'requires_custom' => '0'];
        $this->post(route('admin.products.store'), $attributes)->assertSessionHasNoErrors();
        $product = Product::where('name', 'Produk contoh')->firstOrFail();
        $this->get(route('admin.products.edit', $product))->assertOk();
        $this->put(route('admin.products.update', $product), array_replace($attributes, ['price' => -1]))->assertSessionHasErrors('price');
        $this->put(route('admin.products.update', $product), array_replace($attributes, ['stock' => -1]))->assertSessionHasErrors('stock');
        $this->put(route('admin.products.update', $product), array_replace($attributes, ['price' => 15000]))->assertSessionHasNoErrors();
        $this->delete(route('admin.categories.destroy', $category))->assertSessionHasErrors('category');
        $this->delete(route('admin.products.destroy', $product))->assertSessionHasNoErrors();
        $this->assertFalse($product->fresh()->is_active);
        $empty = Category::create(['name' => 'Kosong']);
        $this->delete(route('admin.categories.destroy', $empty))->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('categories', ['id' => $empty->id]);
    }

    public function test_seed_is_repeatable_and_does_not_reset_admin_password_or_product_edits(): void
    {
        config(['demo.admin_email' => 'admin@example.test', 'demo.admin_password' => 'original-password-123']);
        $this->seed(DatabaseSeeder::class);
        $admin = User::where('email', 'admin@example.test')->firstOrFail();
        $this->assertSame('admin', $admin->role);
        $this->product()->update(['price' => 12345]);
        config(['demo.admin_password' => 'replacement-password-123']);
        $this->seed(DatabaseSeeder::class);
        $this->assertTrue(Hash::check('original-password-123', $admin->fresh()->password));
        $this->assertDatabaseCount('products', 6);
        $this->assertSame(12345, $this->product()->price);
    }
}
