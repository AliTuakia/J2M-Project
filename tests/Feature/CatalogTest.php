<?php

namespace Tests\Feature;

use App\Models\Product;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_confirmed_product_photos_render_without_changing_product_data(): void
    {
        config(['demo.admin_email' => null, 'demo.admin_password' => null]);
        $this->seed(DatabaseSeeder::class);
        $photos = [
            'Busa' => 'busa.jpeg',
            'Fiber / tulangan jok' => 'fiber.jpeg',
            'Kulit jok biasa' => 'kulit polos.jpeg',
            'Kulit jok motif' => 'kulit motif.jpeg',
            'Latex' => 'latex.jpeg',
            'Satu set jok custom' => 'kulit motif(1).jpeg',
        ];
        $catalog = $this->get(route('catalog'))->assertOk();
        $home = $this->get('/')->assertOk();

        foreach ($photos as $name => $filename) {
            $product = Product::where('name', $name)->firstOrFail();
            $original = $product->getAttributes();
            $path = 'images/products/'.$filename;
            $this->assertSame($path, $product->imagePath());
            $this->assertFileExists(public_path($path));
            $this->assertSame('image/jpeg', getimagesize(public_path($path))['mime']);
            $source = 'src="'.asset(str_replace(' ', '%20', $path)).'"';
            $catalog->assertSee($source, false);
            $home->assertSee($source, false);
            $this->get(route('products.show', $product))->assertOk()->assertSee($source, false);
            $this->assertSame($original, $product->fresh()->getAttributes());
        }

        $custom = Product::where('name', 'Satu set jok custom')->firstOrFail();
        $this->assertTrue($custom->usesIllustration());
        $this->assertSame(300000, $custom->price);
        $catalog->assertSee('FOTO HANYA ILUSTRASI');
        $this->get(route('products.show', $custom))->assertOk()
            ->assertSee('FOTO HANYA ILUSTRASI')
            ->assertSee('Foto merupakan referensi visual, bukan desain yang wajib diikuti.')
            ->assertSee('Jok motor custom yang dapat disesuaikan dengan selera Anda. Pelanggan dapat menentukan kombinasi bahan, warna, motif, tekstur, dan model jahitan sesuai keinginan.');
        $this->assertNull((new Product(['name' => 'Produk tanpa pemetaan']))->imagePath());
    }

    public function test_public_content_uses_business_brief_without_legacy_placeholders(): void
    {
        config(['demo.admin_email' => null, 'demo.admin_password' => null]);
        $this->seed(DatabaseSeeder::class);

        $this->get('/')
            ->assertOk()
            ->assertSee('Kategori produk')
            ->assertSee('Tentang J2M')
            ->assertSee('Jasa custom jok');

        foreach (['/', route('catalog')] as $url) {
            $this->get($url)->assertOk()
                ->assertDontSee('DEMO KULIAH')
                ->assertDontSee('Foto produk belum tersedia')
                ->assertDontSee('Stok demo:')
                ->assertSee('Tidak ada transaksi nyata.');
        }

        foreach (Product::all() as $product) {
            $this->get(route('products.show', $product))->assertOk()
                ->assertSee($product->name)
                ->assertSee('Rp'.number_format($product->price, 0, ',', '.'))
                ->assertDontSee($product->description)
                ->assertDontSee('Foto produk belum tersedia')
                ->assertSee('Tidak ada pembayaran atau pengiriman nyata.');
        }

        $product = Product::firstOrFail();
        $product->update(['description' => 'Keterangan dari pengelola.']);
        $this->get(route('products.show', $product))
            ->assertOk()->assertSee('Keterangan dari pengelola.');
    }
}
