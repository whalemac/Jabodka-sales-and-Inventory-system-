<?php

namespace Database\Seeders;

use App\Models\ConsignmentItem;
use App\Models\ConsignmentPartner;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\RawMaterial;
use App\Models\Receipt;
use App\Models\SalesItem;
use App\Models\SalesTransaction;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();

        // ── 1. Suppliers ────────────────────────────────────────────────────────
        $supplierA = Supplier::firstOrCreate(['supplier_name' => 'Outdoor Depot PH'], [
            'contact_details' => '0917-111-2233 · outdoordepot@example.com',
        ]);
        $supplierB = Supplier::firstOrCreate(['supplier_name' => 'Summit Gear Wholesale'], [
            'contact_details' => '0922-444-5566 · summit@example.com',
        ]);

        // ── 2. Consignment Partners ─────────────────────────────────────────────
        $partnerA = ConsignmentPartner::firstOrCreate(['partner_name' => 'TrailBlaze Brands'], [
            'contact_details' => '0933-777-8899',
        ]);
        $partnerB = ConsignmentPartner::firstOrCreate(['partner_name' => 'Peak Supply Co.'], [
            'contact_details' => '0944-000-1122',
        ]);

        // ── 3. Raw Materials (for handmade production) ──────────────────────────
        $materials = [
            ['material_name' => 'Nylon Cord (3mm)',     'unit' => 'meters',   'stock_quantity' => 250.00],
            ['material_name' => 'Canvas Fabric',         'unit' => 'meters',   'stock_quantity' => 80.00],
            ['material_name' => 'YKK Zipper (30cm)',     'unit' => 'pcs',      'stock_quantity' => 120.00],
            ['material_name' => 'Buckle Clip',           'unit' => 'pcs',      'stock_quantity' => 200.00],
            ['material_name' => 'D-Ring (25mm)',         'unit' => 'pcs',      'stock_quantity' => 150.00],
            ['material_name' => 'Paracord (4mm)',        'unit' => 'spools',   'stock_quantity' => 15.00],
        ];
        foreach ($materials as $m) {
            RawMaterial::firstOrCreate(['material_name' => $m['material_name']], $m);
        }

        // ── 4. Products + Variants ───────────────────────────────────────────────

        // HANDMADE
        $handmade = [
            [
                'name' => 'Paracord Bracelet',
                'category' => 'Accessories',
                'base_price' => 180.00,
                'variants' => [
                    ['size' => 'S', 'version' => 'Olive Green',  'stock_count' => 15, 'reorder_level' => 5],
                    ['size' => 'M', 'version' => 'Olive Green',  'stock_count' => 20, 'reorder_level' => 5],
                    ['size' => 'L', 'version' => 'Olive Green',  'stock_count' => 8,  'reorder_level' => 5],
                    ['size' => 'S', 'version' => 'Black',        'stock_count' => 12, 'reorder_level' => 5],
                    ['size' => 'M', 'version' => 'Black',        'stock_count' => 18, 'reorder_level' => 5],
                ],
            ],
            [
                'name' => 'Canvas Daypack',
                'category' => 'Bags',
                'base_price' => 1450.00,
                'variants' => [
                    ['size' => 'Standard', 'version' => 'Khaki',     'stock_count' => 6,  'reorder_level' => 2],
                    ['size' => 'Standard', 'version' => 'Forest',    'stock_count' => 4,  'reorder_level' => 2],
                    ['size' => 'Large',    'version' => 'Khaki',     'stock_count' => 3,  'reorder_level' => 2],
                ],
            ],
            [
                'name' => 'Handmade Neck Gaiter',
                'category' => 'Accessories',
                'base_price' => 250.00,
                'variants' => [
                    ['size' => 'One Size', 'version' => 'Camo',     'stock_count' => 25, 'reorder_level' => 8],
                    ['size' => 'One Size', 'version' => 'Black',    'stock_count' => 30, 'reorder_level' => 8],
                ],
            ],
        ];

        foreach ($handmade as $p) {
            $product = Product::firstOrCreate(
                ['name' => $p['name'], 'source_type' => 'handmade'],
                [
                    'category'    => $p['category'],
                    'base_price'  => $p['base_price'],
                    'supplier_id' => null,
                ]
            );
            foreach ($p['variants'] as $v) {
                ProductVariant::firstOrCreate(
                    ['product_id' => $product->id, 'size' => $v['size'], 'version' => $v['version']],
                    ['stock_count' => $v['stock_count'], 'reorder_level' => $v['reorder_level']]
                );
            }
        }

        // SOURCED (angkat)
        $sourced = [
            [
                'name' => 'Hiking Trekking Poles',
                'category' => 'Equipment',
                'base_price' => 850.00,
                'supplier_id' => $supplierA->id,
                'variants' => [
                    ['size' => 'Pair', 'version' => 'Aluminum', 'stock_count' => 10, 'reorder_level' => 3],
                    ['size' => 'Pair', 'version' => 'Carbon',   'stock_count' => 5,  'reorder_level' => 2],
                ],
            ],
            [
                'name' => 'Camping Headlamp',
                'category' => 'Lighting',
                'base_price' => 450.00,
                'supplier_id' => $supplierA->id,
                'variants' => [
                    ['size' => 'Standard', 'version' => 'Black',  'stock_count' => 20, 'reorder_level' => 5],
                    ['size' => 'Standard', 'version' => 'Yellow', 'stock_count' => 15, 'reorder_level' => 5],
                ],
            ],
            [
                'name' => 'Waterproof Dry Bag',
                'category' => 'Bags',
                'base_price' => 320.00,
                'supplier_id' => $supplierB->id,
                'variants' => [
                    ['size' => '5L',  'version' => 'Blue',   'stock_count' => 18, 'reorder_level' => 5],
                    ['size' => '10L', 'version' => 'Blue',   'stock_count' => 12, 'reorder_level' => 5],
                    ['size' => '20L', 'version' => 'Orange', 'stock_count' => 8,  'reorder_level' => 3],
                    ['size' => '5L',  'version' => 'Orange', 'stock_count' => 2,  'reorder_level' => 5], // low stock
                ],
            ],
            [
                'name' => 'Carabiner Clip (Heavy Duty)',
                'category' => 'Accessories',
                'base_price' => 95.00,
                'supplier_id' => $supplierB->id,
                'variants' => [
                    ['size' => 'D-shape', 'version' => 'Silver', 'stock_count' => 50, 'reorder_level' => 10],
                    ['size' => 'D-shape', 'version' => 'Black',  'stock_count' => 40, 'reorder_level' => 10],
                    ['size' => 'Oval',    'version' => 'Silver', 'stock_count' => 0,  'reorder_level' => 10], // out of stock
                ],
            ],
            [
                'name' => 'Trekking Socks',
                'category' => 'Clothing',
                'base_price' => 165.00,
                'supplier_id' => $supplierA->id,
                'variants' => [
                    ['size' => 'S/M', 'version' => 'Merino Wool', 'stock_count' => 30, 'reorder_level' => 10],
                    ['size' => 'L/XL','version' => 'Merino Wool', 'stock_count' => 25, 'reorder_level' => 10],
                    ['size' => 'S/M', 'version' => 'Synthetic',   'stock_count' => 3,  'reorder_level' => 10], // low stock
                ],
            ],
        ];

        foreach ($sourced as $p) {
            $product = Product::firstOrCreate(
                ['name' => $p['name'], 'source_type' => 'sourced'],
                [
                    'category'    => $p['category'],
                    'base_price'  => $p['base_price'],
                    'supplier_id' => $p['supplier_id'],
                ]
            );
            foreach ($p['variants'] as $v) {
                ProductVariant::firstOrCreate(
                    ['product_id' => $product->id, 'size' => $v['size'], 'version' => $v['version']],
                    ['stock_count' => $v['stock_count'], 'reorder_level' => $v['reorder_level']]
                );
            }
        }

        // CONSIGNMENT
        $consignment = [
            [
                'name' => 'TrailBlaze Windbreaker',
                'category' => 'Clothing',
                'base_price' => 1200.00,
                'partner' => $partnerA,
                'variants' => [
                    ['size' => 'S', 'version' => 'Navy',  'stock_count' => 5,  'reorder_level' => 2, 'base' => 700.00, 'markup' => 500.00, 'units' => 5],
                    ['size' => 'M', 'version' => 'Navy',  'stock_count' => 8,  'reorder_level' => 2, 'base' => 700.00, 'markup' => 500.00, 'units' => 8],
                    ['size' => 'L', 'version' => 'Navy',  'stock_count' => 6,  'reorder_level' => 2, 'base' => 700.00, 'markup' => 500.00, 'units' => 6],
                    ['size' => 'M', 'version' => 'Black', 'stock_count' => 4,  'reorder_level' => 2, 'base' => 700.00, 'markup' => 500.00, 'units' => 4],
                ],
            ],
            [
                'name' => 'Peak Supply Hydration Pack',
                'category' => 'Bags',
                'base_price' => 1800.00,
                'partner' => $partnerB,
                'variants' => [
                    ['size' => '1.5L', 'version' => 'Grey',  'stock_count' => 4, 'reorder_level' => 2, 'base' => 1100.00, 'markup' => 700.00, 'units' => 4],
                    ['size' => '2L',   'version' => 'Grey',  'stock_count' => 3, 'reorder_level' => 2, 'base' => 1200.00, 'markup' => 600.00, 'units' => 3],
                    ['size' => '2L',   'version' => 'Green', 'stock_count' => 1, 'reorder_level' => 2, 'base' => 1200.00, 'markup' => 600.00, 'units' => 3], // low stock
                ],
            ],
        ];

        foreach ($consignment as $p) {
            $product = Product::firstOrCreate(
                ['name' => $p['name'], 'source_type' => 'consignment'],
                [
                    'category'   => $p['category'],
                    'base_price' => $p['base_price'],
                ]
            );
            foreach ($p['variants'] as $v) {
                $variant = ProductVariant::firstOrCreate(
                    ['product_id' => $product->id, 'size' => $v['size'], 'version' => $v['version']],
                    ['stock_count' => $v['stock_count'], 'reorder_level' => $v['reorder_level']]
                );
                // Create consignment item record
                ConsignmentItem::firstOrCreate(
                    ['partner_id' => $p['partner']->id, 'variant_id' => $variant->id],
                    [
                        'units_delivered'   => $v['units'],
                        'agreed_base_price' => $v['base'],
                        'shop_markup'       => $v['markup'],
                        'received_at'       => now()->subDays(30),
                    ]
                );
            }
        }

        // ── 5. Sample Past Transactions (so the walk-in index isn't empty) ─────
        // Only seed if no transactions exist yet
        if (SalesTransaction::count() === 0) {
            $this->seedSampleTransactions($admin);
        }
    }

    private function seedSampleTransactions(User $admin): void
    {
        $carabiner = ProductVariant::whereHas('product', fn ($q) => $q->where('name', 'Carabiner Clip (Heavy Duty)'))
            ->where('size', 'D-shape')->where('version', 'Silver')->first();
        $bracelet  = ProductVariant::whereHas('product', fn ($q) => $q->where('name', 'Paracord Bracelet'))
            ->where('size', 'M')->where('version', 'Black')->first();
        $headlamp  = ProductVariant::whereHas('product', fn ($q) => $q->where('name', 'Camping Headlamp'))
            ->where('version', 'Black')->first();
        $socks     = ProductVariant::whereHas('product', fn ($q) => $q->where('name', 'Trekking Socks'))
            ->where('size', 'S/M')->where('version', 'Merino Wool')->first();

        if (! $carabiner || ! $bracelet || ! $headlamp || ! $socks) {
            return; // products not seeded yet, skip
        }

        // Transaction 1 – 3 days ago, cash, with receipt
        DB::transaction(function () use ($admin, $bracelet, $headlamp) {
            $customer1 = Customer::create([
                'name' => 'Maria Santos', 'buyer_type' => 'walk_in', 'contact_number' => '0917-123-4567',
            ]);
            $tx1 = SalesTransaction::create([
                'customer_id'      => $customer1->id,
                'user_id'          => $admin->id,
                'channel'          => 'walk_in',
                'transaction_date' => now()->subDays(3)->setTime(10, 15),
            ]);
            SalesItem::create(['transaction_id' => $tx1->id, 'variant_id' => $bracelet->id,  'quantity' => 2, 'price_at_sale' => 180.00]);
            SalesItem::create(['transaction_id' => $tx1->id, 'variant_id' => $headlamp->id,  'quantity' => 1, 'price_at_sale' => 450.00]);
            $bracelet->decrement('stock_count', 2);
            $headlamp->decrement('stock_count', 1);
            Payment::create([
                'transaction_id' => $tx1->id, 'user_id' => $admin->id,
                'payment_method' => 'cash', 'amount_paid' => 900.00,
                'payment_status' => 'confirmed', 'payment_date' => $tx1->transaction_date,
            ]);
            Receipt::create([
                'transaction_id' => $tx1->id, 'user_id' => $admin->id,
                'receipt_number' => 'JBK-'.now()->year.'-000001',
                'issued_at'      => $tx1->transaction_date,
                'reprint_count'  => 0,
            ]);
        });

        // Transaction 2 – yesterday, GCash, no receipt
        DB::transaction(function () use ($admin, $carabiner, $socks) {
            $tx2 = SalesTransaction::create([
                'customer_id'      => null,
                'user_id'          => $admin->id,
                'channel'          => 'walk_in',
                'transaction_date' => now()->subDays(1)->setTime(14, 30),
            ]);
            SalesItem::create(['transaction_id' => $tx2->id, 'variant_id' => $carabiner->id, 'quantity' => 3, 'price_at_sale' => 95.00]);
            SalesItem::create(['transaction_id' => $tx2->id, 'variant_id' => $socks->id,     'quantity' => 2, 'price_at_sale' => 165.00]);
            $carabiner->decrement('stock_count', 3);
            $socks->decrement('stock_count', 2);
            Payment::create([
                'transaction_id' => $tx2->id, 'user_id' => $admin->id,
                'payment_method' => 'gcash', 'amount_paid' => 615.00,
                'payment_status' => 'confirmed', 'payment_date' => $tx2->transaction_date,
                'reference_number' => '09876543210',
            ]);
        });

        // Transaction 3 – today, cash
        DB::transaction(function () use ($admin, $headlamp) {
            $customer3 = Customer::create([
                'name' => 'Juan dela Cruz', 'buyer_type' => 'walk_in', 'contact_number' => '0922-987-6543',
            ]);
            $tx3 = SalesTransaction::create([
                'customer_id'      => $customer3->id,
                'user_id'          => $admin->id,
                'channel'          => 'walk_in',
                'transaction_date' => now()->setTime(9, 5),
            ]);
            SalesItem::create(['transaction_id' => $tx3->id, 'variant_id' => $headlamp->id, 'quantity' => 1, 'price_at_sale' => 450.00]);
            $headlamp->decrement('stock_count', 1);
            Payment::create([
                'transaction_id' => $tx3->id, 'user_id' => $admin->id,
                'payment_method' => 'cash', 'amount_paid' => 500.00,
                'payment_status' => 'confirmed', 'payment_date' => $tx3->transaction_date,
            ]);
        });
    }
}
