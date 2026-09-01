<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\User;
use App\Services\InventoryLedger;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $this->user('Superadmin', 'superadmin@fashiondialzena.com', UserRole::Superadmin);
        $owner = $this->user('Admin', 'admin@fashiondialzena.com', UserRole::Admin);
        $staff = $this->user('Staf Toko', 'staf@fashiondialzena.com', UserRole::Staff);
        $sales = $this->user('Tim Penjualan', 'sales@fashiondialzena.com', UserRole::Sales);

        Setting::setValue('wa_number', '6287777626067');

        $categoryIds = $this->categories();
        $variants = $this->catalog($categoryIds, $owner, $staff);

        $this->demoOperations($variants, $owner, $staff, $sales);
    }

    private function user(string $name, string $email, UserRole $role): User
    {
        return User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make('admin123'),
                'role' => $role,
            ]
        );
    }

    /**
     * @return array<string, int>
     */
    private function categories(): array
    {
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

        return $ids;
    }

    /**
     * @param  array<string, int>  $categoryIds
     * @return array<string, ProductVariant>
     */
    private function catalog(array $categoryIds, User $owner, User $staff): array
    {
        $ledger = app(InventoryLedger::class);
        $receivedAt = now()->startOfMonth()->toDateString();

        $items = [
            ['Floral Midi Dress', 'dress', 289000, 8, 'https://images.unsplash.com/photo-1496747611176-843222e1e57c?auto=format&fit=crop&w=600&q=80', 'https://images.unsplash.com/photo-1572804013309-59a883bafcb2?auto=format&fit=crop&w=600&q=80', null, true, false, true, $owner],
            ['Knit Cardigan Soft', 'outerwear', 199000, 5, 'https://images.unsplash.com/photo-1551488831-00ddcb6c6bd3?auto=format&fit=crop&w=600&q=80', 'https://images.unsplash.com/photo-1434389677669-e08b4cac3105?auto=format&fit=crop&w=600&q=80', null, true, false, false, $owner],
            ['Pleated Skirt', 'rok', 179000, 10, 'https://images.unsplash.com/photo-1583496664520-85d9c7f7fd4e?auto=format&fit=crop&w=600&q=80', 'https://images.unsplash.com/photo-1595777457583-95e059d581b8?auto=format&fit=crop&w=600&q=80', null, true, false, false, $owner],
            ['Linen Blouse', 'atasan', 169000, 12, 'https://images.unsplash.com/photo-1598033129183-c4f50c736f10?auto=format&fit=crop&w=600&q=80', 'https://images.unsplash.com/photo-1564257631407-4deb1f72d992?auto=format&fit=crop&w=600&q=80', null, true, false, false, $staff],
            ['Wide Leg Pants', 'celana', 219000, 6, 'https://images.unsplash.com/photo-1473966968600-fa801b869a1a?auto=format&fit=crop&w=600&q=80', 'https://images.unsplash.com/photo-1594633312681-425a7b956cc3?auto=format&fit=crop&w=600&q=80', null, false, false, false, $staff],
            ['Tote Bag Canvas', 'aksesoris', 149000, 15, 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?auto=format&fit=crop&w=600&q=80', 'https://images.unsplash.com/photo-1590874103328-eac89a683008?auto=format&fit=crop&w=600&q=80', null, false, false, false, $staff],
            ['Classic White Dress', 'dress', 259000, 4, 'https://images.unsplash.com/photo-1515372039744-b8f02a3ae446?auto=format&fit=crop&w=600&q=80', 'https://images.unsplash.com/photo-1539008835657-9e8e9680c956?auto=format&fit=crop&w=600&q=80', 4.9, false, true, true, $owner],
            ['Ruffle Top', 'atasan', 159000, 9, 'https://images.unsplash.com/photo-1564257631407-4deb1f72d992?auto=format&fit=crop&w=600&q=80', 'https://images.unsplash.com/photo-1581044777550-4cfa60707c03?auto=format&fit=crop&w=600&q=80', 4.8, false, true, false, $staff],
            ['High Waist Jeans', 'celana', 249000, 7, 'https://images.unsplash.com/photo-1542272604-787c3835535d?auto=format&fit=crop&w=600&q=80', 'https://images.unsplash.com/photo-1475178626620-a4d074967ade?auto=format&fit=crop&w=600&q=80', 4.9, false, true, false, $owner],
            ['Pearl Earrings', 'aksesoris', 89000, 20, 'https://images.unsplash.com/photo-1535632066927-ab7c9ab60908?auto=format&fit=crop&w=600&q=80', 'https://images.unsplash.com/photo-1611591437281-460bfbe1220a?auto=format&fit=crop&w=600&q=80', 5.0, false, true, true, $owner],
            ['Draft Sample Outer', 'outerwear', 99000, 2, 'https://images.unsplash.com/photo-1551488831-00ddcb6c6bd3?auto=format&fit=crop&w=600&q=80', 'https://images.unsplash.com/photo-1434389677669-e08b4cac3105?auto=format&fit=crop&w=600&q=80', null, false, false, false, $owner, false],
        ];

        $variants = [];

        foreach ($items as $item) {
            /** @var User $creator */
            $creator = $item[10];
            $product = Product::query()->updateOrCreate(
                ['name' => $item[0]],
                [
                    'category_id' => $categoryIds[$item[1]],
                    'created_by' => $creator->id,
                    'img_front' => $item[4],
                    'img_back' => $item[5],
                    'rating' => $item[6],
                    'is_new' => $item[7],
                    'is_best_seller' => $item[8],
                    'is_featured' => $item[9],
                    'is_active' => $item[11] ?? true,
                ]
            );

            $sku = strtoupper(Str::slug($item[0], '-'));
            $variants[$sku] = $this->upsertVariant(
                $ledger,
                $product,
                $creator,
                $sku,
                '',
                '',
                (int) $item[2],
                (int) $item[3],
                $receivedAt
            );
        }

        $floral = Product::query()->where('name', 'Floral Midi Dress')->firstOrFail();
        foreach ([['S', 4, true], ['M', 4, true], ['L', 3, false]] as [$size, $qty, $active]) {
            $sku = 'FLORAL-MIDI-'.$size;
            $variants[$sku] = $this->upsertVariant(
                $ledger,
                $floral,
                $owner,
                $sku,
                'Floral',
                $size,
                289000,
                $qty,
                $receivedAt,
                $active
            );
        }

        $pants = Product::query()->where('name', 'Wide Leg Pants')->firstOrFail();
        $variants['WIDE-LEG-HITAM'] = $this->upsertVariant(
            $ledger, $pants, $staff, 'WIDE-LEG-HITAM', 'Hitam', '', 219000, 5, $receivedAt
        );

        $skirt = Product::query()->where('name', 'Pleated Skirt')->firstOrFail();
        $variants['PLEATED-SKIRT-S'] = $this->upsertVariant(
            $ledger, $skirt, $owner, 'PLEATED-SKIRT-S', '', 'S', 179000, 4, $receivedAt
        );

        return $variants;
    }

    private function upsertVariant(
        InventoryLedger $ledger,
        Product $product,
        User $creator,
        string $sku,
        string $color,
        string $size,
        int $sellPrice,
        int $openingQty,
        string $receivedAt,
        bool $active = true
    ): ProductVariant {
        $variant = $product->variants()->firstOrNew(['sku' => $sku]);
        $isNew = ! $variant->exists;
        $cost = (int) round($sellPrice * 0.55);

        $variant->fill([
            'created_by' => $creator->id,
            'color' => $color,
            'size' => $size,
            'cost_price' => $cost,
            'sell_price' => $sellPrice,
            'is_active' => $active,
        ]);

        if ($isNew) {
            $variant->stock = 0;
        }

        $variant->save();

        if ($isNew && $openingQty > 0) {
            $this->receive($ledger, $variant, $creator, $openingQty, $cost, $receivedAt, 'Stok awal', 'Seeder');
        }

        return $variant->fresh();
    }

    /**
     * @param  array<string, ProductVariant>  $variants
     */
    private function demoOperations(array $variants, User $owner, User $staff, User $sales): void
    {
        if (Order::query()->exists()) {
            return;
        }

        $ledger = app(InventoryLedger::class);
        $jeans = $variants['HIGH-WAIST-JEANS'];
        $pearls = $variants['PEARL-EARRINGS'];

        $this->receive(
            $ledger,
            $jeans,
            $staff,
            3,
            (int) $jeans->cost_price,
            now()->startOfMonth()->addDays(8)->toDateString(),
            'Supplier Bandung',
            'INV-SUP-001'
        );

        $this->receive(
            $ledger,
            $pearls,
            $owner,
            5,
            40000,
            now()->startOfMonth()->addDays(14)->toDateString(),
            'Gudang cabang',
            'HPP beda — rata-rata tertimbang'
        );

        $multi = $this->sell($ledger, $owner, now()->subDays(12)->toDateString(), 'whatsapp', 'Sinta', [
            [$variants['FLORAL-MIDI-S'], 1],
            [$variants['FLORAL-MIDI-M'], 1],
            [$variants['LINEN-BLOUSE'], 1],
        ], 'Transfer BCA');

        $this->sell($ledger, $staff, now()->subDays(8)->toDateString(), 'offline', 'Dewi', [
            [$variants['CLASSIC-WHITE-DRESS'], 2],
        ], 'COD toko');

        $this->sell($ledger, $sales, now()->subDays(6)->toDateString(), 'website', 'Lina', [
            [$variants['KNIT-CARDIGAN-SOFT'], 1],
            [$variants['PLEATED-SKIRT-S'], 1],
        ], 'Checkout website');

        $this->sell($ledger, $staff, now()->subDays(4)->toDateString(), 'shopee', 'Maya', [
            [$variants['HIGH-WAIST-JEANS'], 1],
            [$variants['TOTE-BAG-CANVAS'], 1, 129000],
        ], 'Diskon Shopee');

        $tokped = $this->sell($ledger, $sales, now()->subDays(2)->toDateString(), 'tokopedia', 'Raka', [
            [$variants['RUFFLE-TOP'], 2],
        ], 'Pesanan Tokopedia');

        $this->sell($ledger, $owner, now()->subDay()->toDateString(), 'lainnya', null, [
            [$variants['PEARL-EARRINGS'], 1],
        ], 'Reseller');

        $offlinePants = $this->sell($ledger, $sales, now()->toDateString(), 'offline', 'Andi', [
            [$variants['WIDE-LEG-HITAM'], 1],
        ], null);

        $linenItem = $multi->items()->where('product_variant_id', $variants['LINEN-BLOUSE']->id)->firstOrFail();
        $this->refund($ledger, $sales, $linenItem->id, 1, now()->subDays(10)->toDateString(), [
            'reason' => 'Ukuran kurang pas',
            'condition' => 'baik',
            'restocked' => true,
        ]);

        $ruffleItem = $tokped->items()->firstOrFail();
        $this->refund($ledger, $staff, $ruffleItem->id, 1, now()->subDay()->toDateString(), [
            'reason' => 'Cacat jahitan',
            'condition' => 'cacat',
            'restocked' => true,
            'refund_amount' => 100000,
        ]);

        $pantsItem = $offlinePants->items()->firstOrFail();
        $this->refund($ledger, $owner, $pantsItem->id, 1, now()->toDateString(), [
            'reason' => 'Sobek di pengiriman',
            'condition' => 'rusak',
            'restocked' => false,
            'refund_amount' => 150000,
        ]);
    }

    /**
     * @param  list<array{0: ProductVariant, 1: int, 2?: int}>  $lines
     */
    private function sell(
        InventoryLedger $ledger,
        User $actor,
        string $soldAt,
        string $channel,
        ?string $customer,
        array $lines,
        ?string $note
    ): Order {
        return DB::transaction(function () use ($ledger, $actor, $soldAt, $channel, $customer, $lines, $note) {
            $prepared = collect($lines)->map(function (array $line) {
                $variant = $line[0]->fresh();
                $quantity = $line[1];

                return [
                    'variant' => $variant,
                    'quantity' => $quantity,
                    'unit_price' => isset($line[2]) ? (int) $line[2] : (int) $variant->sell_price,
                    'unit_cost' => (int) $variant->cost_price,
                ];
            });

            $order = Order::query()->create([
                'code' => 'TMP-'.uniqid(),
                'created_by' => $actor->id,
                'channel' => $channel,
                'customer_name' => $customer,
                'note' => $note,
                'subtotal' => (int) $prepared->sum(fn (array $item) => $item['unit_price'] * $item['quantity']),
                'cogs_total' => (int) $prepared->sum(fn (array $item) => $item['unit_cost'] * $item['quantity']),
                'sold_at' => $soldAt,
            ]);

            $order->update([
                'code' => 'ORD-'.str_pad((string) $order->id, 5, '0', STR_PAD_LEFT),
            ]);

            foreach ($prepared as $item) {
                $line = $order->items()->create([
                    'product_id' => $item['variant']->product_id,
                    'product_variant_id' => $item['variant']->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'unit_cost' => $item['unit_cost'],
                    'total' => $item['unit_price'] * $item['quantity'],
                    'cogs_total' => $item['unit_cost'] * $item['quantity'],
                ]);

                $ledger->issue(
                    $item['variant'],
                    $item['quantity'],
                    $item['unit_cost'],
                    'order_item',
                    $line->id,
                    $soldAt,
                    $order->code
                );
            }

            return $order->fresh('items');
        });
    }

    private function receive(
        InventoryLedger $ledger,
        ProductVariant $variant,
        User $actor,
        int $quantity,
        int $unitCost,
        string $receivedAt,
        string $source,
        ?string $note
    ): void {
        DB::transaction(function () use ($ledger, $variant, $actor, $quantity, $unitCost, $receivedAt, $source, $note) {
            $entry = $variant->stockIns()->create([
                'created_by' => $actor->id,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'source' => $source,
                'note' => $note,
                'received_at' => $receivedAt,
            ]);

            $ledger->receive($variant, $quantity, $unitCost, 'stock_in', $entry->id, $receivedAt, $source);
        });
    }

    /**
     * @param  array{reason: string, condition: string, restocked: bool, refund_amount?: int}  $options
     */
    private function refund(
        InventoryLedger $ledger,
        User $actor,
        int $orderItemId,
        int $quantity,
        string $returnedAt,
        array $options
    ): void {
        DB::transaction(function () use ($ledger, $actor, $orderItemId, $quantity, $returnedAt, $options) {
            $line = OrderItem::query()->with(['order', 'variant'])->findOrFail($orderItemId);
            $unitCost = (int) $line->unit_cost;
            $restocked = $options['restocked'];
            $refund = $options['refund_amount'] ?? ((int) $line->unit_price * $quantity);

            $return = $line->returns()->create([
                'created_by' => $actor->id,
                'quantity' => $quantity,
                'reason' => $options['reason'],
                'restocked' => $restocked,
                'condition' => $options['condition'],
                'refund_amount' => $refund,
                'cogs_reversed' => $restocked ? $unitCost * $quantity : 0,
                'note' => 'Seeder',
                'returned_at' => $returnedAt,
            ]);

            if ($restocked) {
                $ledger->restock(
                    $line->variant,
                    $quantity,
                    $unitCost,
                    'order_return',
                    $return->id,
                    $returnedAt,
                    $line->order->code
                );
            }
        });
    }
}
