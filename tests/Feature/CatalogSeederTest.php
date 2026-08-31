<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderReturn;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockIn;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CatalogSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_fills_every_admin_input_example(): void
    {
        $this->seed(CatalogSeeder::class);

        $owner = User::query()->where('email', 'admin@fashiondialzena.com')->first();
        $staff = User::query()->where('email', 'staf@fashiondialzena.com')->first();
        $sales = User::query()->where('email', 'sales@fashiondialzena.com')->first();

        $this->assertNotNull($owner);
        $this->assertTrue($owner->isOwner());
        $this->assertTrue(Hash::check('admin123', $owner->password));
        $this->assertSame(UserRole::Staff, $staff?->role);
        $this->assertSame(UserRole::Sales, $sales?->role);

        $this->assertSame(11, Product::query()->count());
        $this->assertTrue(Product::query()->where('name', 'Draft Sample Outer')->where('is_active', false)->exists());
        $this->assertTrue(Product::query()->where('is_new', true)->exists());
        $this->assertTrue(Product::query()->where('is_best_seller', true)->exists());
        $this->assertTrue(Product::query()->where('is_featured', true)->exists());
        $this->assertTrue(Product::query()->whereNotNull('rating')->exists());

        $this->assertTrue(ProductVariant::query()->where('sku', 'FLORAL-MIDI-S')->where('color', 'Floral')->where('size', 'S')->exists());
        $this->assertTrue(ProductVariant::query()->where('sku', 'WIDE-LEG-HITAM')->where('color', 'Hitam')->where('size', '')->exists());
        $this->assertTrue(ProductVariant::query()->where('sku', 'PLEATED-SKIRT-S')->where('color', '')->where('size', 'S')->exists());
        $this->assertTrue(ProductVariant::query()->where('sku', 'FLORAL-MIDI-L')->where('is_active', false)->exists());

        $this->assertTrue(StockIn::query()->where('source', 'Supplier Bandung')->where('note', 'INV-SUP-001')->exists());
        $this->assertTrue(StockIn::query()->where('unit_cost', 40000)->where('source', 'Gudang cabang')->exists());

        $channels = Order::query()->pluck('channel')->unique()->sort()->values()->all();
        $this->assertSame(['lainnya', 'offline', 'shopee', 'tokopedia', 'website', 'whatsapp'], $channels);
        $this->assertTrue(Order::query()->whereNull('customer_name')->where('note', 'Reseller')->exists());
        $this->assertTrue(OrderItem::query()->where('unit_price', 129000)->exists());

        $this->assertSame(7, Order::query()->count());
        $this->assertSame(3, OrderReturn::query()->count());
        $this->assertTrue(OrderReturn::query()->where('condition', 'baik')->where('restocked', true)->exists());
        $this->assertTrue(OrderReturn::query()->where('condition', 'cacat')->where('refund_amount', 100000)->exists());
        $this->assertTrue(OrderReturn::query()->where('condition', 'rusak')->where('restocked', false)->exists());
    }

    public function test_seeder_does_not_duplicate_demo_orders(): void
    {
        $this->seed(CatalogSeeder::class);
        $this->seed(CatalogSeeder::class);

        $this->assertSame(7, Order::query()->count());
        $this->assertSame(3, OrderReturn::query()->count());
        $this->assertSame(3, User::query()->count());
    }
}
