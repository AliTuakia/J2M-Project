<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $categories = collect(['Bahan jok', 'Jok custom'])
            ->mapWithKeys(fn ($name) => [$name => Category::firstOrCreate(['name' => $name])->id]);

        foreach ([
            ['Kulit jok biasa', 50000, 'Harga untuk kebutuhan satu jok motor.'],
            ['Kulit jok motif', 80000, 'Harga untuk kebutuhan satu jok motor.'],
            ['Busa', 100000, 'Deskripsi lengkap dan satuan produk belum tersedia.'],
            ['Fiber / tulangan jok', 120000, 'Deskripsi lengkap dan satuan produk belum tersedia.'],
            ['Latex', 80000, 'Deskripsi lengkap dan satuan produk belum tersedia.'],
            ['Satu set jok custom', 300000, 'Harga tetap untuk demo. Isi tipe motor dan catatan permintaan Anda.'],
        ] as [$name, $price, $description]) {
            $custom = $name === 'Satu set jok custom';

            Product::firstOrCreate(['name' => $name], [
                'category_id' => $categories[$custom ? 'Jok custom' : 'Bahan jok'],
                'description' => $description,
                'price' => $price,
                'stock' => 10,
                'requires_custom' => $custom,
            ]);
        }

        $email = config('demo.admin_email');
        $password = config('demo.admin_password');

        if ($email && $password && ! User::where('email', $email)->exists()) {
            if (! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12) {
                throw new \RuntimeException('Email admin harus valid dan password minimal 12 karakter.');
            }

            $admin = new User([
                'name' => 'Admin Demo J2M',
                'email' => $email,
                'password' => $password,
            ]);
            $admin->role = 'admin';
            $admin->save();
        }
    }
}
