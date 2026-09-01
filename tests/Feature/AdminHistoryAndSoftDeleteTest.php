<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminHistoryAndSoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_soft_deleted_product_is_hidden_from_storefront_and_only_superadmin_can_restore(): void
    {
        $owner = User::factory()->owner()->create();
        $staff = User::factory()->staff()->create();
        $product = $this->product('Dress Soft Delete');

        $this->actingAs($owner)
            ->delete(route('admin.products.destroy', $product))
            ->assertRedirect(route('admin.products.index'));

        $this->assertSoftDeleted('products', ['id' => $product->id]);
        $this->get('/')->assertDontSee('Dress Soft Delete');

        $this->actingAs($owner)
            ->get(route('admin.products.index', ['trashed' => 1]))
            ->assertOk()
            ->assertSee('Dress Soft Delete')
            ->assertDontSee('Pulihkan');

        $this->actingAs($staff)
            ->post(route('admin.products.restore', $product))
            ->assertRedirect(route('admin.products.index'));

        $this->actingAs($owner)
            ->post(route('admin.products.restore', $product))
            ->assertRedirect(route('admin.dashboard'));

        $this->assertSoftDeleted('products', ['id' => $product->id]);

        $super = User::factory()->superadmin()->create();

        $this->actingAs($super)
            ->get(route('admin.products.index', ['trashed' => 1]))
            ->assertOk()
            ->assertSee('Dress Soft Delete')
            ->assertSee('Pulihkan');

        $this->actingAs($super)
            ->post(route('admin.products.restore', $product))
            ->assertRedirect();

        $this->assertNotSoftDeleted('products', ['id' => $product->id]);
        $this->get('/')->assertSee('Dress Soft Delete');
    }

    public function test_admin_actions_are_written_to_history_for_superadmin_only(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)
            ->post(route('admin.categories.store'), [
                'name' => 'Aksesoris Riwayat',
                'sort_order' => 9,
            ])
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'created',
            'subject_type' => Category::class,
            'subject_label' => 'Aksesoris Riwayat',
            'user_id' => $owner->id,
        ]);

        $this->actingAs($owner)
            ->get(route('admin.history.index'))
            ->assertRedirect(route('admin.dashboard'));

        $super = User::factory()->superadmin()->create();

        $this->actingAs($super)
            ->get(route('admin.history.index'))
            ->assertOk()
            ->assertSee('Aksesoris Riwayat')
            ->assertSee('Dibuat');

        $this->actingAs($super)
            ->get(route('admin.dashboard'))
            ->assertSee('href="'.route('admin.history.index').'"', false);

        $this->actingAs($owner)
            ->get(route('admin.dashboard'))
            ->assertDontSee('href="'.route('admin.history.index').'"', false);
    }

    public function test_deleted_user_cannot_login_until_superadmin_restores(): void
    {
        $super = User::factory()->superadmin()->create();
        User::factory()->owner()->create();
        $staff = User::factory()->staff()->create([
            'email' => 'staf@alzena.test',
            'password' => 'password123',
        ]);

        $this->actingAs($super)
            ->delete(route('admin.users.destroy', $staff))
            ->assertRedirect(route('admin.users.index'));

        $this->assertSoftDeleted('users', ['id' => $staff->id]);

        $this->post(route('admin.logout'));

        $this->from(route('admin.login'))->post(route('admin.login.store'), [
            'email' => 'staf@alzena.test',
            'password' => 'password123',
        ])->assertSessionHasErrors('email');

        $this->actingAs($super)
            ->post(route('admin.users.restore', $staff))
            ->assertRedirect();

        $this->assertNotSoftDeleted('users', ['id' => $staff->id]);
    }

    private function product(string $name): Product
    {
        $category = Category::query()->create([
            'name' => 'Dress',
            'slug' => 'dress-soft',
        ]);

        return Product::query()->create([
            'name' => $name,
            'category_id' => $category->id,
            'img_front' => 'https://example.com/front.jpg',
            'img_back' => 'https://example.com/back.jpg',
            'is_active' => true,
            'is_new' => true,
        ]);
    }
}
