<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminProductImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_photos_are_saved_on_the_vps_with_searchable_names(): void
    {
        Storage::fake('public');

        $owner = User::factory()->owner()->create();
        $category = Category::query()->create([
            'name' => 'Atasan',
            'slug' => 'atasan',
        ]);

        $this->actingAs($owner)->post(route('admin.products.store'), [
            'name' => 'Kaos Polos Putih',
            'category_id' => $category->id,
            'sku' => 'KAOS-PUTIH-M',
            'color' => 'Putih',
            'size' => 'M',
            'stock' => 3,
            'cost_price' => '50000',
            'sell_price' => '120000',
            'is_active' => '1',
            'photo_front' => UploadedFile::fake()->image('front.jpg', 200, 240),
            'photo_back' => UploadedFile::fake()->image('back.jpg', 200, 240),
        ])->assertRedirect();

        $product = Product::query()->sole();
        $this->assertStringStartsWith('/storage/products/kaos-polos-putih-depan-', $product->img_front);
        $this->assertStringStartsWith('/storage/products/kaos-polos-putih-belakang-', $product->img_back);
        $this->assertStringEndsWith('.jpg', $product->img_front);

        Storage::disk('public')->assertExists('products/'.basename($product->img_front));
        Storage::disk('public')->assertExists('products/'.basename($product->img_back));
    }

    public function test_replacing_a_local_photo_overwrites_the_same_file(): void
    {
        Storage::fake('public');

        $owner = User::factory()->owner()->create();
        $category = Category::query()->create([
            'name' => 'Atasan',
            'slug' => 'atasan',
        ]);

        $this->actingAs($owner)->post(route('admin.products.store'), [
            'name' => 'Kaos Navy',
            'category_id' => $category->id,
            'sku' => 'KAOS-NAVY-M',
            'stock' => 0,
            'cost_price' => '50000',
            'sell_price' => '120000',
            'photo_front' => UploadedFile::fake()->image('front.jpg', 120, 120),
            'photo_back' => UploadedFile::fake()->image('back.jpg', 120, 120),
        ]);

        $product = Product::query()->sole();
        $frontUrl = $product->img_front;
        $filesBefore = collect(Storage::disk('public')->files('products'))->sort()->values();

        $this->actingAs($owner)->put(route('admin.products.update', $product), [
            'name' => 'Kaos Navy',
            'category_id' => $category->id,
            'photo_front' => UploadedFile::fake()->image('front-new.jpg', 180, 180),
            'is_active' => '1',
        ])->assertRedirect(route('admin.products.edit', $product));

        $product->refresh();
        $this->assertSame($frontUrl, $product->img_front);
        Storage::disk('public')->assertExists('products/'.basename($frontUrl));
        $this->assertSame(
            $filesBefore->all(),
            collect(Storage::disk('public')->files('products'))->sort()->values()->all()
        );
    }

    public function test_existing_cloudinary_urls_can_stay_on_update(): void
    {
        Storage::fake('public');

        $owner = User::factory()->owner()->create();
        $category = Category::query()->create([
            'name' => 'Dress',
            'slug' => 'dress',
        ]);
        $product = Product::query()->create([
            'name' => 'Classic Dress',
            'category_id' => $category->id,
            'img_front' => 'https://res.cloudinary.com/demo/image/upload/front.jpg',
            'img_back' => 'https://res.cloudinary.com/demo/image/upload/back.jpg',
            'is_active' => true,
        ]);

        $this->actingAs($owner)->put(route('admin.products.update', $product), [
            'name' => 'Classic Dress',
            'category_id' => $category->id,
            'is_active' => '1',
        ])->assertRedirect(route('admin.products.edit', $product));

        $product->refresh();
        $this->assertSame('https://res.cloudinary.com/demo/image/upload/front.jpg', $product->img_front);
        $this->assertSame('https://res.cloudinary.com/demo/image/upload/back.jpg', $product->img_back);
    }
}
