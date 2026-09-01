<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockIn;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_is_redirected_away_from_owner_pages(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)->get(route('admin.ledger'))
            ->assertRedirect(route('admin.products.index'));
        $this->actingAs($staff)->get(route('admin.settings.edit'))
            ->assertRedirect(route('admin.products.index'));
        $this->actingAs($staff)->get(route('admin.users.index'))
            ->assertRedirect(route('admin.products.index'));
        $this->actingAs($staff)->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.products.index'));
    }

    public function test_staff_can_open_operational_pages(): void
    {
        $staff = User::factory()->staff()->create();

        $this->actingAs($staff)->get(route('admin.products.index'))->assertOk();
        $this->actingAs($staff)->get(route('admin.sales.index'))->assertOk()->assertDontSee('>HPP<', false);
        $this->actingAs($staff)->get(route('admin.stock-ins.index'))->assertOk()->assertDontSee('HPP masuk');
        $this->actingAs($staff)->get(route('admin.returns.index'))->assertOk();
    }

    public function test_staff_cannot_delete_an_order(): void
    {
        $staff = User::factory()->staff()->create();
        [$variant] = $this->variants();
        $order = $this->recordSale($variant);

        $this->actingAs($staff)
            ->delete(route('admin.sales.destroy', $order))
            ->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('orders', ['id' => $order->id]);
    }

    public function test_staff_stock_in_ignores_submitted_hpp(): void
    {
        $staff = User::factory()->staff()->create();
        [$variant] = $this->variants();

        $this->actingAs($staff)->post(route('admin.stock-ins.store'), [
            'product_variant_id' => $variant->id,
            'quantity' => 2,
            'received_at' => '2026-08-28',
            'unit_cost' => '1000',
        ])->assertRedirect(route('admin.stock-ins.index'));

        $entry = StockIn::query()->latest('id')->first();
        $this->assertSame(80000, (int) $entry->unit_cost);
        $this->assertSame(12, (int) $variant->fresh()->stock);
    }

    public function test_staff_cannot_change_sku_hpp(): void
    {
        $staff = User::factory()->staff()->create();
        [$variant] = $this->variants();

        $this->actingAs($staff)->put(route('admin.products.variants.update', [$variant->product, $variant]), [
            'sku' => $variant->sku,
            'color' => $variant->color,
            'size' => $variant->size,
            'cost_price' => '1000',
            'sell_price' => '200000',
            'is_active' => '1',
        ])->assertRedirect(route('admin.products.edit', $variant->product));

        $this->assertSame(80000, (int) $variant->fresh()->cost_price);
        $this->assertSame(200000, (int) $variant->fresh()->sell_price);
    }

    public function test_owner_cannot_manage_users(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)->get(route('admin.users.index'))
            ->assertRedirect(route('admin.dashboard'));
        $this->actingAs($owner)->get(route('admin.history.index'))
            ->assertRedirect(route('admin.dashboard'));

        $this->actingAs($owner)->post(route('admin.users.store'), [
            'name' => 'Kasir',
            'email' => 'kasir@alzena.test',
            'password' => 'password1',
            'role' => UserRole::Staff->value,
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertDatabaseMissing('users', [
            'email' => 'kasir@alzena.test',
        ]);
    }

    public function test_superadmin_can_manage_users(): void
    {
        $super = User::factory()->superadmin()->create();

        $this->actingAs($super)->post(route('admin.users.store'), [
            'name' => 'Kasir',
            'email' => 'kasir@alzena.test',
            'password' => 'password1',
            'role' => UserRole::Staff->value,
        ])->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'email' => 'kasir@alzena.test',
            'role' => UserRole::Staff->value,
        ]);
    }

    public function test_superadmin_cannot_remove_last_superadmin_or_last_owner(): void
    {
        $super = User::factory()->superadmin()->create();
        $owner = User::factory()->owner()->create();

        $this->actingAs($super)
            ->delete(route('admin.users.destroy', $super))
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $super->id, 'deleted_at' => null]);

        $this->actingAs($super)
            ->put(route('admin.users.update', $super), [
                'name' => $super->name,
                'email' => $super->email,
                'role' => UserRole::Owner->value,
            ])
            ->assertSessionHasErrors();

        $this->assertTrue($super->fresh()->isSuperadmin());

        $this->actingAs($super)
            ->delete(route('admin.users.destroy', $owner))
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $owner->id, 'deleted_at' => null]);

        $this->actingAs($super)
            ->put(route('admin.users.update', $owner), [
                'name' => $owner->name,
                'email' => $owner->email,
                'role' => UserRole::Staff->value,
            ])
            ->assertSessionHasErrors();

        $this->assertTrue($owner->fresh()->isOwner());
    }

    public function test_sales_role_is_limited_to_selling(): void
    {
        $sales = User::factory()->sales()->create();

        $this->actingAs($sales)->get(route('admin.sales.index'))->assertOk();
        $this->actingAs($sales)->get(route('admin.returns.index'))->assertOk();
        $this->actingAs($sales)->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.sales.index'));
        $this->actingAs($sales)->get(route('admin.products.index'))
            ->assertRedirect(route('admin.sales.index'));
        $this->actingAs($sales)->get(route('admin.stock-ins.index'))
            ->assertRedirect(route('admin.sales.index'));
        $this->actingAs($sales)->get(route('admin.categories.index'))
            ->assertRedirect(route('admin.sales.index'));
        $this->actingAs($sales)->get(route('admin.ledger'))
            ->assertRedirect(route('admin.sales.index'));

        [$variant] = $this->variants();
        $this->actingAs($sales)->post(route('admin.sales.store'), [
            'sold_at' => '2026-08-22',
            'channel' => 'whatsapp',
            'items' => [
                ['product_variant_id' => $variant->id, 'quantity' => 1, 'unit_price' => ''],
            ],
        ])->assertRedirect(route('admin.sales.index'));

        $this->assertSame($sales->id, Order::query()->sole()->created_by);
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

    private function recordSale(ProductVariant $variant): Order
    {
        $this->actingAs(User::factory()->owner()->create())->post(route('admin.sales.store'), [
            'sold_at' => '2026-08-22',
            'channel' => 'whatsapp',
            'items' => [
                [
                    'product_variant_id' => $variant->id,
                    'quantity' => 1,
                    'unit_price' => '',
                ],
            ],
        ]);

        return Order::query()->sole();
    }
}
