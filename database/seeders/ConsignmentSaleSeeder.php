<?php

namespace Database\Seeders;

use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\SalesItem;
use App\Models\SalesTransaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ConsignmentSaleSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('username', 'admin')->firstOrFail();

        // Only seed if no consignment sales exist yet
        $alreadySeeded = SalesTransaction::where('channel', 'walk_in')
            ->whereHas('items.variant.product', fn ($q) => $q->where('source_type', 'consignment'))
            ->exists();

        if ($alreadySeeded) {
            $this->command->info('Consignment sales already seeded, skipping.');
            return;
        }

        // Grab consignment variants
        $windbreaker = ProductVariant::whereHas('product', fn ($q) => $q->where('name', 'TrailBlaze Windbreaker'))
            ->where('size', 'M')->where('version', 'Navy')->first();
        $hydration = ProductVariant::whereHas('product', fn ($q) => $q->where('name', 'Peak Supply Hydration Pack'))
            ->where('size', '1.5L')->first();

        if (! $windbreaker || ! $hydration) {
            $this->command->warn('Consignment variants not found — run DemoDataSeeder first.');
            return;
        }

        // Walk-in sale of 2 consignment items (unsettled)
        DB::transaction(function () use ($admin, $windbreaker, $hydration) {
            $tx = SalesTransaction::create([
                'customer_id'      => null,
                'user_id'          => $admin->id,
                'channel'          => 'walk_in',
                'transaction_date' => now()->subDays(10),
            ]);
            SalesItem::create(['transaction_id' => $tx->id, 'variant_id' => $windbreaker->id, 'quantity' => 1, 'price_at_sale' => 1200.00]);
            SalesItem::create(['transaction_id' => $tx->id, 'variant_id' => $hydration->id,   'quantity' => 1, 'price_at_sale' => 1800.00]);
            $windbreaker->decrement('stock_count', 1);
            $hydration->decrement('stock_count', 1);
            Payment::create([
                'transaction_id' => $tx->id,
                'user_id'        => $admin->id,
                'payment_method' => 'cash',
                'amount_paid'    => 3000.00,
                'payment_status' => 'confirmed',
                'payment_date'   => now()->subDays(10),
            ]);
        });

        // A second sale (a different partner)
        DB::transaction(function () use ($admin, $windbreaker) {
            $tx = SalesTransaction::create([
                'customer_id'      => null,
                'user_id'          => $admin->id,
                'channel'          => 'walk_in',
                'transaction_date' => now()->subDays(3),
            ]);
            SalesItem::create(['transaction_id' => $tx->id, 'variant_id' => $windbreaker->id, 'quantity' => 2, 'price_at_sale' => 1200.00]);
            $windbreaker->decrement('stock_count', 2);
            Payment::create([
                'transaction_id' => $tx->id,
                'user_id'        => $admin->id,
                'payment_method' => 'gcash',
                'amount_paid'    => 2400.00,
                'payment_status' => 'confirmed',
                'payment_date'   => now()->subDays(3),
                'reference_number' => '09999888777',
            ]);
        });

        $this->command->info('Consignment sales seeded (3 unsettled items).');
    }
}
