<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockIn;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAccountAndAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_sale_and_stock_in_record_the_actor(): void
    {
        $staff = User::factory()->staff()->create(['name' => 'Kasir Satu']);
        [$variant] = $this->variants();

        $this->actingAs($staff)->post(route('admin.sales.store'), [
            'sold_at' => '2026-08-28',
            'channel' => 'whatsapp',
            'items' => [
                ['product_variant_id' => $variant->id, 'quantity' => 1, 'unit_price' => ''],
            ],
        ])->assertRedirect(route('admin.sales.index'));

        $this->assertSame($staff->id, Order::query()->sole()->created_by);

        $this->actingAs($staff)->post(route('admin.stock-ins.store'), [
            'product_variant_id' => $variant->id,
            'quantity' => 2,
            'received_at' => '2026-08-28',
        ])->assertRedirect(route('admin.stock-ins.index'));

        $this->assertSame($staff->id, StockIn::query()->latest('id')->value('created_by'));

        $this->actingAs($staff)
            ->get(route('admin.sales.index', ['from' => '2026-08-01', 'to' => '2026-08-28']))
            ->assertOk()
            ->assertSee('Kasir Satu')
            ->assertSee('Tercatat atas nama');
    }

    public function test_sales_can_be_filtered_by_who_recorded_them(): void
    {
        $staff = User::factory()->staff()->create(['name' => 'Kasir Satu']);
        $owner = User::factory()->owner()->create(['name' => 'Owner Toko']);
        [$variant] = $this->variants();

        $this->actingAs($staff)->post(route('admin.sales.store'), [
            'sold_at' => '2026-08-20',
            'channel' => 'whatsapp',
            'customer_name' => 'Pembeli Staff',
            'items' => [
                ['product_variant_id' => $variant->id, 'quantity' => 1, 'unit_price' => ''],
            ],
        ]);

        $this->actingAs($owner)->post(route('admin.sales.store'), [
            'sold_at' => '2026-08-21',
            'channel' => 'offline',
            'customer_name' => 'Pembeli Owner',
            'items' => [
                ['product_variant_id' => $variant->id, 'quantity' => 1, 'unit_price' => ''],
            ],
        ]);

        $this->actingAs($owner)
            ->get(route('admin.sales.index', [
                'from' => '2026-08-01',
                'to' => '2026-08-28',
                'recorded_by' => $staff->id,
            ]))
            ->assertOk()
            ->assertSee('Pembeli Staff')
            ->assertDontSee('Pembeli Owner');
    }

    public function test_user_can_change_own_password(): void
    {
        $user = User::factory()->staff()->create([
            'password' => 'password1',
        ]);

        $this->actingAs($user)->put(route('admin.account.update'), [
            'current_password' => 'password1',
            'password' => 'password2',
            'password_confirmation' => 'password2',
        ])->assertRedirect(route('admin.account.edit'));

        $this->assertTrue(Hash::check('password2', $user->fresh()->password));
    }

    public function test_login_is_throttled_after_five_failures(): void
    {
        User::factory()->owner()->create([
            'email' => 'admin@fashiondialzena.com',
            'password' => 'secret123',
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->from(route('admin.login'))->post(route('admin.login.store'), [
                'email' => 'admin@fashiondialzena.com',
                'password' => 'wrong',
            ])->assertRedirect(route('admin.login'));
        }

        $this->from(route('admin.login'))->post(route('admin.login.store'), [
            'email' => 'admin@fashiondialzena.com',
            'password' => 'wrong',
        ])->assertSessionHasErrors('email');

        $this->assertStringContainsString(
            'Terlalu banyak percobaan',
            session('errors')->first('email')
        );
    }

    public function test_owner_dashboard_shows_who_sold_and_who_added_catalog(): void
    {
        $this->travelTo('2026-08-28 12:00:00');

        $owner = User::factory()->owner()->create(['name' => 'Owner Toko']);
        $staff = User::factory()->staff()->create(['name' => 'Staf Katalog']);
        $sales = User::factory()->sales()->create(['name' => 'Sales A']);
        [$variant] = $this->variants();

        $product = Product::query()->create([
            'name' => 'Kaos Baru',
            'category_id' => $variant->product->category_id,
            'img_front' => 'https://example.com/front.jpg',
            'img_back' => 'https://example.com/back.jpg',
            'is_active' => true,
            'created_by' => $staff->id,
            'created_at' => '2026-08-20 09:00:00',
        ]);
        $product->variants()->create([
            'sku' => 'KAOS-BARU-M',
            'color' => 'Navy',
            'size' => 'M',
            'stock' => 0,
            'cost_price' => 50000,
            'sell_price' => 120000,
            'is_active' => true,
            'created_by' => $staff->id,
            'created_at' => '2026-08-20 09:00:00',
        ]);

        $this->actingAs($sales)->post(route('admin.sales.store'), [
            'sold_at' => '2026-08-21',
            'channel' => 'whatsapp',
            'items' => [
                ['product_variant_id' => $variant->id, 'quantity' => 2, 'unit_price' => ''],
            ],
        ]);

        $this->actingAs($staff)->post(route('admin.sales.store'), [
            'sold_at' => '2026-08-22',
            'channel' => 'offline',
            'items' => [
                ['product_variant_id' => $variant->id, 'quantity' => 1, 'unit_price' => ''],
            ],
        ]);

        $this->actingAs($owner)
            ->get(route('admin.dashboard', ['from' => '2026-08-01', 'to' => '2026-08-28']))
            ->assertOk()
            ->assertSee('Performa penjualan')
            ->assertSee('Performa input katalog')
            ->assertDontSee('Performa tim')
            ->assertSee('Staf Katalog')
            ->assertSee('Sales A');

        $this->actingAs($staff)
            ->get(route('admin.dashboard', ['from' => '2026-08-01', 'to' => '2026-08-28']))
            ->assertRedirect(route('admin.products.index'));
    }

    /**
     * @return array{ProductVariant}
     */
    private function variants(): array
    {
        $category = Category::query()->create([
            'name' => 'Dress',
            'slug' => 'dress',
        ]);

        $product = Product::query()->create([
            'name' => 'Classic Dress',
            'category_id' => $category->id,
            'img_front' => 'https://example.com/front.jpg',
            'img_back' => 'https://example.com/back.jpg',
            'is_active' => true,
        ]);

        return [
            $product->variants()->create([
                'sku' => 'DRESS-WHITE-M',
                'color' => 'White',
                'size' => 'M',
                'stock' => 10,
                'cost_price' => 80000,
                'sell_price' => 100000,
                'is_active' => true,
            ]),
        ];
    }
}
