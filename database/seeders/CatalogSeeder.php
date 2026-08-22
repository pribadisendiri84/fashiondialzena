<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use App\Services\InventoryLedger;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@fashiondialzena.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('admin123'),
            ]
        );

        Setting::setValue('wa_number', '6287777626067');

        $categories = [
            ['Dress', 'dress', 'https://images.unsplash.com/photo-1496747611176-843222e1e57c?auto=format&fit=crop&w=400&q=80', 1],
            ['Atasan', 'atasan', 'https://images.unsplash.com/photo-1485968579580-b6d095142e6e?auto=format&fit=crop&w=400&q=80', 2],
            ['Celana', 'celana', 'https://images.unsplash.com/photo-1542272604-787c3835535d?auto=format&fit=crop&w=400&q=80', 3],
            ['Aksesoris', 'aksesoris', 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&w=400&q=80', 4],
            ['Outerwear', 'outerwear', 'https://images.unsplash.com/photo-1551488831-00ddcb6c6bd3?auto=format&fit=crop&w=400&q=80', 5],
            ['Rok', 'rok', 'https://images.unsplash.com/photo-1583496664520-85d9c7f7fd4e?auto=format&fit=crop&w=400&q=80', 6],
        ];

        $ids = [];
        foreach ($categories as [$name, $slug, $image, $order]) {
            $ids[$slug] = Category::query()->updateOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'image_url' => $image, 'sort_order' => $order]
            )->id;
        }

        $items = [
            ['Floral Midi Dress', 'dress', 289000, 8, 'https://images.unsplash.com/photo-1496747611176-843222e1e57c?auto=format&fit=crop&w=600&q=80', 'https://images.unsplash.com/photo-1572804013309-59a883bafcb2?auto=format&fit=crop&w=600&q=80', null, true, false, true],
            ['Knit Cardigan Soft', 'outerwear', 199000, 5, 'https://images.unsplash.com/photo-1551488831-00ddcb6c6bd3?auto=format&fit=crop&w=600&q=80', 'https://images.unsplash.com/photo-1434389677669-e08b4cac3105?auto=format&fit=crop&w=600&q=80', null, true, false, false],
            ['Pleated Skirt', 'rok', 179000, 10, 'https://images.unsplash.com/photo-1583496664520-85d9c7f7fd4e?auto=format&fit=crop&w=600&q=80', 'https://images.unsplash.com/photo-1595777457583-95e059d581b8?auto=format&fit=crop&w=600&q=80', null, true, false, false],
            ['Linen Blouse', 'atasan', 169000, 12, 'https://images.unsplash.com/photo-1598033129183-c4f50c736f10?auto=format&fit=crop&w=600&q=80', 'https://images.unsplash.com/photo-1564257631407-4deb1f72d992?auto=format&fit=crop&w=600&q=80', null, true, false, false],
            ['Wide Leg Pants', 'celana', 219000, 6, 'https://images.unsplash.com/photo-1473966968600-fa801b869a1a?auto=format&fit=crop&w=600&q=80', 'https://images.unsplash.com/photo-1594633312681-425a7b956cc3?auto=format&fit=crop&w=600&q=80', null, false, false, false],
            ['Tote Bag Canvas', 'aksesoris', 149000, 15, 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&w=600&q=80', 'https://images.unsplash.com/photo-1590874103328-eac89a683008?auto=format&fit=crop&w=600&q=80', null, false, false, false],
            ['Classic White Dress', 'dress', 259000, 4, 'https://images.unsplash.com/photo-1515372039744-b8f02a3ae446?auto=format&fit=crop&w=600&q=80', 'https://images.unsplash.com/photo-1539008835657-9e8e9680c956?auto=format&fit=crop&w=600&q=80', 4.9, false, true, true],
            ['Ruffle Top', 'atasan', 159000, 9, 'https://images.unsplash.com/photo-1564257631407-4deb1f72d992?auto=format&fit=crop&w=600&q=80', 'https://images.unsplash.com/photo-1581044777550-4cfa60707c03?auto=format&fit=crop&w=600&q=80', 4.8, false, true, false],
            ['High Waist Jeans', 'celana', 249000, 7, 'https://images.unsplash.com/photo-1542272604-787c3835535d?auto=format&fit=crop&w=600&q=80', 'https://images.unsplash.com/photo-1475178626620-a4d074967ade?auto=format&fit=crop&w=600&q=80', 4.9, false, true, false],
            ['Pearl Earrings', 'aksesoris', 89000, 20, 'https://images.unsplash.com/photo-1535632066927-ab7c9ab60908?auto=format&fit=crop&w=600&q=80', 'https://images.unsplash.com/photo-1611591437281-460bfbe1220a?auto=format&fit=crop&w=600&q=80', 5.0, false, true, true],
        ];

        $ledger = app(InventoryLedger::class);

        foreach ($items as $item) {
            $product = Product::query()->updateOrCreate(
                ['name' => $item[0]],
                [
                    'category_id' => $ids[$item[1]],
                    'img_front' => $item[4],
                    'img_back' => $item[5],
                    'rating' => $item[6],
                    'is_new' => $item[7],
                    'is_best_seller' => $item[8],
                    'is_featured' => $item[9],
                    'is_active' => true,
                ]
            );

            $sku = strtoupper(Str::slug($item[0], '-'));
            $variant = $product->variants()->firstOrNew(['sku' => $sku]);
            $isNew = ! $variant->exists;
            $cost = (int) round($item[2] * 0.55);

            $variant->fill([
                'color' => '',
                'size' => '',
                'cost_price' => $cost,
                'sell_price' => $item[2],
                'is_active' => true,
            ]);

            if ($isNew) {
                $variant->stock = 0;
            }

            $variant->save();

            if ($isNew && $item[3] > 0) {
                $entry = $variant->stockIns()->create([
                    'quantity' => $item[3],
                    'unit_cost' => $cost,
                    'source' => 'Stok awal',
                    'note' => 'Seeder',
                    'received_at' => now()->toDateString(),
                ]);

                $ledger->receive($variant, $item[3], $cost, 'stock_in', $entry->id, now()->toDateString(), 'Stok awal');
            }
        }
    }
}
