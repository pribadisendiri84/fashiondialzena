<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMultiItemOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_record_multiple_skus_in_one_order(): void
    {
        $this->actingAs(User::factory()->create());
        [$first, $second] = $this->variants();

        $response = $this->post(route('admin.sales.store'), [
            'sold_at' => '2026-08-22',
            'channel' => 'whatsapp',
            'customer_name' => 'Raddy',
            'note' => 'Lunas',
            'items' => [
                [
                    'product_variant_id' => $first->id,
                    'quantity' => 2,
                    'unit_price' => '',
                ],
                [
                    'product_variant_id' => $second->id,
                    'quantity' => 1,
                    'unit_price' => '175000',
                ],
            ],
        ]);

        $response->assertRedirect(route('admin.sales.index'));

        $order = Order::query()->with('items')->sole();
        $this->assertSame('ORD-00001', $order->code);
        $this->assertCount(2, $order->items);
        $this->assertSame(375000, (int) $order->subtotal);
        $this->assertSame(190000, (int) $order->cogs_total);
        $this->assertSame(8, (int) $first->fresh()->stock);
        $this->assertSame(4, (int) $second->fresh()->stock);
        $this->assertSame(2, StockMovement::query()->where('type', 'sale')->count());
    }

    public function test_multi_item_order_rolls_back_when_any_sku_has_insufficient_stock(): void
    {
        $this->actingAs(User::factory()->create());
        [$first, $second] = $this->variants();

        $response = $this->from(route('admin.sales.index'))->post(route('admin.sales.store'), [
            'sold_at' => '2026-08-22',
            'channel' => 'whatsapp',
            'items' => [
                [
                    'product_variant_id' => $first->id,
                    'quantity' => 2,
                    'unit_price' => '',
                ],
                [
                    'product_variant_id' => $second->id,
                    'quantity' => 99,
                    'unit_price' => '',
                ],
            ],
        ]);

        $response->assertRedirect(route('admin.sales.index'));
        $response->assertSessionHasErrors();
        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(10, (int) $first->fresh()->stock);
        $this->assertSame(5, (int) $second->fresh()->stock);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_same_sku_cannot_be_added_twice_to_one_order(): void
    {
        $this->actingAs(User::factory()->create());
        [$first] = $this->variants();

        $response = $this->from(route('admin.sales.index'))->post(route('admin.sales.store'), [
            'sold_at' => '2026-08-22',
            'channel' => 'offline',
            'items' => [
                ['product_variant_id' => $first->id, 'quantity' => 1, 'unit_price' => ''],
                ['product_variant_id' => $first->id, 'quantity' => 1, 'unit_price' => ''],
            ],
        ]);

        $response->assertRedirect(route('admin.sales.index'));
        $response->assertSessionHasErrors('items.1.product_variant_id');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_deleting_multi_item_order_restores_every_sku(): void
    {
        $this->actingAs(User::factory()->create());
        [$first, $second] = $this->variants();

        $this->post(route('admin.sales.store'), [
            'sold_at' => '2026-08-22',
            'channel' => 'offline',
            'items' => [
                ['product_variant_id' => $first->id, 'quantity' => 3, 'unit_price' => ''],
                ['product_variant_id' => $second->id, 'quantity' => 2, 'unit_price' => ''],
            ],
        ])->assertRedirect(route('admin.sales.index'));

        $order = Order::query()->sole();
        $this->assertSame(7, (int) $first->fresh()->stock);
        $this->assertSame(3, (int) $second->fresh()->stock);

        $this->delete(route('admin.sales.destroy', $order))
            ->assertRedirect(route('admin.sales.index'));

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(10, (int) $first->fresh()->stock);
        $this->assertSame(5, (int) $second->fresh()->stock);
        $this->assertSame(2, StockMovement::query()->where('type', 'sale')->count());
        $this->assertSame(2, StockMovement::query()->where('type', 'reversal')->count());
    }

    /**
     * @return array{ProductVariant, ProductVariant}
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
            $product->variants()->create([
                'sku' => 'DRESS-BLACK-L',
                'color' => 'Black',
                'size' => 'L',
                'stock' => 5,
                'cost_price' => 30000,
                'sell_price' => 150000,
                'is_active' => true,
            ]),
        ];
    }
}
