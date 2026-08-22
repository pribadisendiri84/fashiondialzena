<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('sku', 80)->unique();
            $table->string('color', 80)->nullable();
            $table->string('size', 40)->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->unsignedInteger('cost_price')->default(0);
            $table->unsignedInteger('sell_price');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $products = DB::table('products')->orderBy('id')->get();
        foreach ($products as $product) {
            $base = strtoupper(Str::slug($product->name, '-'));
            $sku = $base !== '' ? $base : 'SKU-'.$product->id;
            $candidate = $sku;
            $n = 1;
            while (DB::table('product_variants')->where('sku', $candidate)->exists()) {
                $candidate = $sku.'-'.$n++;
            }

            DB::table('product_variants')->insert([
                'product_id' => $product->id,
                'sku' => $candidate,
                'color' => null,
                'size' => null,
                'stock' => (int) $product->stock,
                'cost_price' => 0,
                'sell_price' => (int) $product->price,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('product_variant_id')->nullable()->after('product_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('unit_cost')->default(0)->after('unit_price');
            $table->unsignedInteger('cogs_total')->default(0)->after('total');
            $table->string('channel', 40)->default('whatsapp')->after('cogs_total');
            $table->string('sale_code', 40)->nullable()->after('channel');
        });

        $variantByProduct = DB::table('product_variants')->pluck('id', 'product_id');
        foreach (DB::table('sales')->orderBy('id')->get() as $sale) {
            $variantId = $variantByProduct[$sale->product_id] ?? null;
            if (! $variantId) {
                continue;
            }
            DB::table('sales')->where('id', $sale->id)->update([
                'product_variant_id' => $variantId,
                'unit_cost' => 0,
                'cogs_total' => 0,
                'channel' => 'whatsapp',
                'sale_code' => 'WA-'.str_pad((string) $sale->id, 5, '0', STR_PAD_LEFT),
            ]);
        }

        Schema::table('stock_ins', function (Blueprint $table) {
            $table->foreignId('product_variant_id')->nullable()->after('product_id')->constrained()->restrictOnDelete();
        });

        foreach (DB::table('stock_ins')->orderBy('id')->get() as $row) {
            $variantId = $variantByProduct[$row->product_id] ?? null;
            if ($variantId) {
                DB::table('stock_ins')->where('id', $row->id)->update([
                    'product_variant_id' => $variantId,
                ]);
            }
        }

        Schema::create('sale_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->string('reason')->nullable();
            $table->boolean('restocked')->default(true);
            $table->string('condition', 40)->nullable();
            $table->unsignedInteger('refund_amount')->default(0);
            $table->string('note')->nullable();
            $table->date('returned_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_returns');

        Schema::table('stock_ins', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_variant_id');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_variant_id');
            $table->dropColumn(['unit_cost', 'cogs_total', 'channel', 'sale_code']);
        });

        Schema::dropIfExists('product_variants');
    }
};
