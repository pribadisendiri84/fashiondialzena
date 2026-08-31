<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOpsExportAndStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_dashboard_lists_low_stock_skus(): void
    {
        $owner = User::factory()->owner()->create();
        [$low, $ok] = $this->variants();
        $low->update(['stock' => 2]);
        $ok->update(['stock' => 8]);

        $this->actingAs($owner)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('SKU menipis')
            ->assertSee('LOW-SKU')
            ->assertSee('Stok masuk')
            ->assertSee('Riwayat')
            ->assertDontSee('OK-SKU');
    }

    public function test_owner_can_export_sales_and_ledger_csv(): void
    {
        $owner = User::factory()->owner()->create(['name' => 'Owner Toko']);
        [$variant] = $this->variants();

        $this->actingAs($owner)->post(route('admin.sales.store'), [
            'sold_at' => '2026-08-21',
            'channel' => 'whatsapp',
            'customer_name' => 'Ibu Sari',
            'items' => [
                ['product_variant_id' => $variant->id, 'quantity' => 2, 'unit_price' => ''],
            ],
        ])->assertRedirect(route('admin.sales.index'));

        $sales = $this->actingAs($owner)->get(route('admin.sales.export', [
            'from' => '2026-08-01',
            'to' => '2026-08-31',
        ]));
        $sales->assertOk();
        $sales->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $salesCsv = $sales->streamedContent();
        $this->assertStringContainsString('LOW-SKU', $salesCsv);
        $this->assertStringContainsString('Ibu Sari', $salesCsv);
        $this->assertStringContainsString('HPP satuan', $salesCsv);
        $this->assertStringContainsString('Laba', $salesCsv);

        $ledger = $this->actingAs($owner)->get(route('admin.ledger.export', [
            'from' => '2026-08-01',
            'to' => '2026-08-31',
        ]));
        $ledger->assertOk();
        $ledgerCsv = $ledger->streamedContent();
        $this->assertStringContainsString('Nilai stok', $ledgerCsv);
        $this->assertStringContainsString('LOW-SKU', $ledgerCsv);
    }

    public function test_staff_can_export_sales_without_hpp_but_not_ledger(): void
    {
        $staff = User::factory()->staff()->create();
        [$variant] = $this->variants();

        $this->actingAs($staff)->post(route('admin.sales.store'), [
            'sold_at' => '2026-08-21',
            'channel' => 'offline',
            'items' => [
                ['product_variant_id' => $variant->id, 'quantity' => 1, 'unit_price' => ''],
            ],
        ]);

        $csv = $this->actingAs($staff)
            ->get(route('admin.sales.export', ['from' => '2026-08-01', 'to' => '2026-08-31']))
            ->assertOk()
            ->streamedContent();
        $this->assertStringContainsString('LOW-SKU', $csv);
        $this->assertStringNotContainsString('HPP satuan', $csv);

        $this->actingAs($staff)
            ->get(route('admin.ledger.export', ['from' => '2026-08-01', 'to' => '2026-08-31']))
            ->assertRedirect(route('admin.products.index'));
    }

    public function test_owner_and_staff_can_see_sku_movement_history(): void
    {
        $owner = User::factory()->owner()->create();
        $staff = User::factory()->staff()->create();
        [$variant] = $this->variants();

        $this->actingAs($staff)->post(route('admin.stock-ins.store'), [
            'product_variant_id' => $variant->id,
            'quantity' => 3,
            'received_at' => '2026-08-20',
        ]);

        $this->actingAs($staff)->post(route('admin.sales.store'), [
            'sold_at' => '2026-08-21',
            'channel' => 'whatsapp',
            'items' => [
                ['product_variant_id' => $variant->id, 'quantity' => 1, 'unit_price' => ''],
            ],
        ]);

        $this->assertTrue(StockMovement::query()->where('product_variant_id', $variant->id)->exists());

        $this->actingAs($owner)
            ->get(route('admin.variants.movements', $variant))
            ->assertOk()
            ->assertSee('Riwayat stok')
            ->assertSee('Stok masuk')
            ->assertSee('Terjual')
            ->assertSee('HPP');

        $this->actingAs($staff)
            ->get(route('admin.variants.movements', $variant))
            ->assertOk()
            ->assertSee('Terjual')
            ->assertDontSee('>HPP<', false);
    }

    public function test_sales_cannot_open_sku_movement_history(): void
    {
        $sales = User::factory()->sales()->create();
        [$variant] = $this->variants();

        $this->actingAs($sales)
            ->get(route('admin.variants.movements', $variant))
            ->assertRedirect(route('admin.sales.index'));
    }

    /**
     * @return array{0: ProductVariant, 1: ProductVariant}
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
                'sku' => 'LOW-SKU',
                'color' => 'White',
                'size' => 'M',
                'stock' => 10,
                'cost_price' => 80000,
                'sell_price' => 100000,
                'is_active' => true,
            ]),
            $product->variants()->create([
                'sku' => 'OK-SKU',
                'color' => 'Black',
                'size' => 'L',
                'stock' => 10,
                'cost_price' => 80000,
                'sell_price' => 120000,
                'is_active' => true,
            ]),
        ];
    }
}
