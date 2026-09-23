<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\SalesItem;
use App\Models\SalesTransaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OnlineOrderSeeder extends Seeder
{
    public function run(): void
    {
        // Skip if online orders already exist
        if (SalesTransaction::where('channel', 'online')->exists()) {
            $this->command->info('Online orders already seeded, skipping.');
            return;
        }

        $admin = User::where('username', 'admin')->firstOrFail();

        $poles  = ProductVariant::whereHas('product', fn ($q) => $q->where('name', 'Hiking Trekking Poles'))
                    ->where('version', 'Aluminum')->first();
        $dryBag = ProductVariant::whereHas('product', fn ($q) => $q->where('name', 'Waterproof Dry Bag'))
                    ->where('size', '10L')->first();
        $windbreaker = ProductVariant::whereHas('product', fn ($q) => $q->where('name', 'TrailBlaze Windbreaker'))
                    ->where('size', 'M')->where('version', 'Navy')->first();
        $headlamp = ProductVariant::whereHas('product', fn ($q) => $q->where('name', 'Camping Headlamp'))
                    ->where('version', 'Yellow')->first();

        // Order 1: Awaiting payment (no payment recorded)
        DB::transaction(function () use ($admin, $poles, $dryBag) {
            if (! $poles || ! $dryBag) return;
            $customer = Customer::create([
                'name'             => 'Ana Reyes',
                'buyer_type'       => 'online',
                'contact_number'   => '0917-555-0001',
                'shipping_address' => 'Block 3 Lot 7, Buhangin, Davao City',
                'landmark'         => 'Near SM Lanang',
            ]);
            $tx = SalesTransaction::create([
                'customer_id'      => $customer->id,
                'user_id'          => $admin->id,
                'channel'          => 'online',
                'transaction_date' => now()->subHours(3),
                'shipping_fee'     => 150.00,
                'courier'          => 'J&T Express',
                'shipment_status'  => 'pending_payment',
            ]);
            SalesItem::create(['transaction_id' => $tx->id, 'variant_id' => $poles->id,  'quantity' => 1, 'price_at_sale' => 850.00]);
            SalesItem::create(['transaction_id' => $tx->id, 'variant_id' => $dryBag->id, 'quantity' => 2, 'price_at_sale' => 320.00]);
            $poles->decrement('stock_count', 1);
            $dryBag->decrement('stock_count', 2);
        });

        // Order 2: Payment confirmed, ready to ship, with tracking
        DB::transaction(function () use ($admin, $windbreaker) {
            if (! $windbreaker) return;
            $customer = Customer::create([
                'name'             => 'Carlo Mendez',
                'buyer_type'       => 'online',
                'contact_number'   => '0922-666-0002',
                'shipping_address' => '45 Matina Road, Matina, Davao City',
                'landmark'         => 'Behind Gaisano Mall',
            ]);
            $tx = SalesTransaction::create([
                'customer_id'      => $customer->id,
                'user_id'          => $admin->id,
                'channel'          => 'online',
                'transaction_date' => now()->subDays(2),
                'shipping_fee'     => 120.00,
                'courier'          => 'J&T Express',
                'tracking_number'  => 'JT420987654321',
                'shipment_status'  => 'ready_to_ship',
            ]);
            SalesItem::create(['transaction_id' => $tx->id, 'variant_id' => $windbreaker->id, 'quantity' => 1, 'price_at_sale' => 1200.00]);
            $windbreaker->decrement('stock_count', 1);
            Payment::create([
                'transaction_id'   => $tx->id,
                'user_id'          => $admin->id,
                'payment_method'   => 'gcash',
                'amount_paid'      => 1320.00,
                'payment_status'   => 'confirmed',
                'payment_date'     => now()->subDays(2),
                'reference_number' => '09123456789',
            ]);
        });

        // Order 3: Shipped
        DB::transaction(function () use ($admin, $headlamp) {
            if (! $headlamp) return;
            $customer = Customer::create([
                'name'             => 'Bea Torres',
                'buyer_type'       => 'online',
                'contact_number'   => '0933-777-0003',
                'shipping_address' => '22 Quirino Ave, Poblacion, Davao City',
                'landmark'         => null,
            ]);
            $tx = SalesTransaction::create([
                'customer_id'      => $customer->id,
                'user_id'          => $admin->id,
                'channel'          => 'online',
                'transaction_date' => now()->subDays(5),
                'shipping_fee'     => 100.00,
                'courier'          => 'LBC',
                'tracking_number'  => 'LBC1122334455',
                'shipment_status'  => 'shipped',
            ]);
            SalesItem::create(['transaction_id' => $tx->id, 'variant_id' => $headlamp->id, 'quantity' => 2, 'price_at_sale' => 450.00]);
            $headlamp->decrement('stock_count', 2);
            Payment::create([
                'transaction_id' => $tx->id,
                'user_id'        => $admin->id,
                'payment_method' => 'gcash',
                'amount_paid'    => 1000.00,
                'payment_status' => 'confirmed',
                'payment_date'   => now()->subDays(5),
                'reference_number' => '09987654321',
            ]);
        });

        $this->command->info('3 sample online orders seeded.');
    }
}
