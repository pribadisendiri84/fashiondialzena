<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        $this->normalizeVariants();
        $this->createOrderTables();
        $this->migrateSales();
        $this->rebuildStockIns();
        $this->createStockMovements();
        $this->backfillMovements();
        $this->dropLegacy();

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // Irreversible structural cleanup.
    }

    private function normalizeVariants(): void
    {
        DB::table('product_variants')->whereNull('color')->update(['color' => '']);
        DB::table('product_variants')->whereNull('size')->update(['size' => '']);

        $seen = [];
        foreach (DB::table('product_variants')->orderBy('id')->get() as $variant) {
            $key = $variant->product_id.'|'.mb_strtolower($variant->color).'|'.mb_strtolower($variant->size);
            if (! isset($seen[$key])) {
                $seen[$key] = true;
                continue;
            }

            DB::table('product_variants')->where('id', $variant->id)->update([
                'size' => trim($variant->size.'-'.$variant->id),
            ]);
        }

        Schema::table('product_variants', function (Blueprint $table) {
            $table->index(['product_id', 'is_active']);
            $table->unique(['product_id', 'color', 'size']);
        });
    }

    private function createOrderTables(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('channel', 40)->index();
            $table->string('customer_name')->nullable();
            $table->string('note')->nullable();
            $table->unsignedInteger('subtotal')->default(0);
            $table->unsignedInteger('cogs_total')->default(0);
            $table->date('sold_at')->index();
            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_variant_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('unit_price');
            $table->unsignedInteger('unit_cost')->default(0);
            $table->unsignedInteger('total');
            $table->unsignedInteger('cogs_total')->default(0);
            $table->timestamps();

            $table->index(['product_variant_id', 'created_at']);
        });

        Schema::create('order_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->string('reason')->nullable();
            $table->boolean('restocked')->default(true);
            $table->string('condition', 40)->nullable();
            $table->unsignedInteger('refund_amount')->default(0);
            $table->unsignedInteger('cogs_reversed')->default(0);
            $table->string('note')->nullable();
            $table->date('returned_at')->index();
            $table->timestamps();
        });
    }

    private function migrateSales(): void
    {
        if (! Schema::hasTable('sales')) {
            return;
        }

        $variantByProduct = DB::table('product_variants')->pluck('id', 'product_id');

        foreach (DB::table('sales')->orderBy('id')->get() as $sale) {
            $variantId = $sale->product_variant_id ?: ($variantByProduct[$sale->product_id] ?? null);
            if (! $variantId) {
                continue;
            }

            $code = $sale->sale_code && ! str_starts_with((string) $sale->sale_code, 'WA-')
                ? $sale->sale_code
                : 'ORD-'.str_pad((string) $sale->id, 5, '0', STR_PAD_LEFT);

            $orderId = DB::table('orders')->insertGetId([
                'code' => $code,
                'channel' => $sale->channel ?: 'lainnya',
                'customer_name' => $sale->customer_name,
                'note' => $sale->note,
                'subtotal' => (int) $sale->total,
                'cogs_total' => (int) ($sale->cogs_total ?? 0),
                'sold_at' => $sale->sold_at,
                'created_at' => $sale->created_at,
                'updated_at' => $sale->updated_at,
            ]);

            $itemId = DB::table('order_items')->insertGetId([
                'order_id' => $orderId,
                'product_id' => $sale->product_id,
                'product_variant_id' => $variantId,
                'quantity' => $sale->quantity,
                'unit_price' => $sale->unit_price,
                'unit_cost' => (int) ($sale->unit_cost ?? 0),
                'total' => $sale->total,
                'cogs_total' => (int) ($sale->cogs_total ?? 0),
                'created_at' => $sale->created_at,
                'updated_at' => $sale->updated_at,
            ]);

            if (Schema::hasTable('sale_returns')) {
                foreach (DB::table('sale_returns')->where('sale_id', $sale->id)->get() as $ret) {
                    $cogsReversed = ! empty($ret->restocked)
                        ? (int) ($sale->unit_cost ?? 0) * (int) $ret->quantity
                        : 0;

                    DB::table('order_returns')->insert([
                        'order_item_id' => $itemId,
                        'quantity' => $ret->quantity,
                        'reason' => $ret->reason,
                        'restocked' => (bool) $ret->restocked,
                        'condition' => $ret->condition,
                        'refund_amount' => (int) $ret->refund_amount,
                        'cogs_reversed' => $cogsReversed,
                        'note' => $ret->note,
                        'returned_at' => $ret->returned_at,
                        'created_at' => $ret->created_at,
                        'updated_at' => $ret->updated_at,
                    ]);
                }
            }
        }
    }

    private function rebuildStockIns(): void
    {
        if (! Schema::hasTable('stock_ins')) {
            return;
        }

        Schema::create('stock_ins_next', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('unit_cost')->default(0);
            $table->string('source')->nullable();
            $table->string('note')->nullable();
            $table->date('received_at')->index();
            $table->timestamps();
        });

        $variantByProduct = DB::table('product_variants')->pluck('id', 'product_id');
        $costs = DB::table('product_variants')->pluck('cost_price', 'id');

        foreach (DB::table('stock_ins')->orderBy('id')->get() as $row) {
            $variantId = $row->product_variant_id ?: ($variantByProduct[$row->product_id] ?? null);
            if (! $variantId) {
                continue;
            }

            DB::table('stock_ins_next')->insert([
                'id' => $row->id,
                'product_variant_id' => $variantId,
                'quantity' => $row->quantity,
                'unit_cost' => (int) ($costs[$variantId] ?? 0),
                'source' => $row->source,
                'note' => $row->note,
                'received_at' => $row->received_at,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        }

        Schema::drop('stock_ins');
        Schema::rename('stock_ins_next', 'stock_ins');
    }

    private function createStockMovements(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_variant_id')->constrained()->restrictOnDelete();
            $table->string('type', 20)->index();
            $table->integer('quantity');
            $table->unsignedInteger('unit_cost')->default(0);
            $table->integer('stock_after')->nullable();
            $table->string('reference_type', 40)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('note')->nullable();
            $table->date('moved_at')->index();
            $table->timestamps();

            $table->index(['reference_type', 'reference_id']);
            $table->index(['product_variant_id', 'moved_at']);
        });
    }

    private function backfillMovements(): void
    {
        $events = [];

        foreach (DB::table('stock_ins')->get() as $row) {
            $events[] = [
                'at' => $row->received_at,
                'id' => $row->id,
                'variant_id' => $row->product_variant_id,
                'type' => 'in',
                'qty' => (int) $row->quantity,
                'cost' => (int) $row->unit_cost,
                'reference_type' => 'stock_in',
                'reference_id' => $row->id,
                'note' => $row->source,
            ];
        }

        foreach (DB::table('order_items')->get() as $row) {
            $order = DB::table('orders')->where('id', $row->order_id)->first();
            $events[] = [
                'at' => $order?->sold_at,
                'id' => $row->id,
                'variant_id' => $row->product_variant_id,
                'type' => 'sale',
                'qty' => -(int) $row->quantity,
                'cost' => (int) $row->unit_cost,
                'reference_type' => 'order_item',
                'reference_id' => $row->id,
                'note' => $order?->code,
            ];
        }

        foreach (DB::table('order_returns')->get() as $row) {
            if (! $row->restocked) {
                continue;
            }
            $item = DB::table('order_items')->where('id', $row->order_item_id)->first();
            $events[] = [
                'at' => $row->returned_at,
                'id' => $row->id,
                'variant_id' => $item?->product_variant_id,
                'type' => 'return',
                'qty' => (int) $row->quantity,
                'cost' => (int) ($item?->unit_cost ?? 0),
                'reference_type' => 'order_return',
                'reference_id' => $row->id,
                'note' => $row->reason,
            ];
        }

        usort($events, function ($a, $b) {
            return [$a['at'], $a['type'], $a['id']] <=> [$b['at'], $b['type'], $b['id']];
        });

        $running = [];
        foreach ($events as $event) {
            if (! $event['variant_id']) {
                continue;
            }
            $running[$event['variant_id']] = ($running[$event['variant_id']] ?? 0) + $event['qty'];

            DB::table('stock_movements')->insert([
                'product_variant_id' => $event['variant_id'],
                'type' => $event['type'],
                'quantity' => $event['qty'],
                'unit_cost' => $event['cost'],
                'stock_after' => $running[$event['variant_id']],
                'reference_type' => $event['reference_type'],
                'reference_id' => $event['reference_id'],
                'note' => $event['note'],
                'moved_at' => $event['at'] ?: now()->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach (DB::table('product_variants')->get() as $variant) {
            $computed = $running[$variant->id] ?? 0;
            $actual = (int) $variant->stock;
            if ($computed === $actual) {
                continue;
            }

            DB::table('stock_movements')->insert([
                'product_variant_id' => $variant->id,
                'type' => 'adjustment',
                'quantity' => $actual - $computed,
                'unit_cost' => (int) $variant->cost_price,
                'stock_after' => $actual,
                'reference_type' => 'opening',
                'reference_id' => $variant->id,
                'note' => 'Penyesuaian migrasi ke ledger',
                'moved_at' => now()->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function dropLegacy(): void
    {
        Schema::dropIfExists('sale_returns');
        Schema::dropIfExists('sales');

        if (Schema::hasColumn('products', 'price')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn(['price', 'stock']);
            });
        }
    }
};
