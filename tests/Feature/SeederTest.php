<?php

namespace Tests\Feature;

use App\Models\ConsignmentItem;
use App\Models\Product;
use App\Models\SalesTransaction;
use Database\Seeders\ConsignmentSaleSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\OnlineOrderSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_seeders_run_against_the_new_schema(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DemoDataSeeder::class);
        $this->seed(OnlineOrderSeeder::class);
        $this->seed(ConsignmentSaleSeeder::class);

        $this->assertGreaterThan(0, Product::count());
        $this->assertGreaterThan(0, SalesTransaction::count());
        $this->assertGreaterThan(0, ConsignmentItem::count());
    }

    public function test_walk_in_rows_are_never_given_a_shipment_status(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DemoDataSeeder::class);
        $this->seed(ConsignmentSaleSeeder::class);

        $walkIns = SalesTransaction::query()->where('channel', 'walk_in')->get();

        $this->assertGreaterThan(0, $walkIns->count());
        $this->assertSame(
            0,
            $walkIns->filter(fn ($tx) => $tx->shipment_status !== null)->count(),
            'Walk-in transactions must keep shipment_status NULL.'
        );
    }

    public function test_seeded_online_orders_start_with_a_shipment_status(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(OnlineOrderSeeder::class);

        $online = SalesTransaction::query()->where('channel', 'online')->get();

        $this->assertGreaterThan(0, $online->count());
        $this->assertSame(
            0,
            $online->filter(fn ($tx) => $tx->shipment_status === null)->count(),
            'Online transactions must always carry a shipment_status.'
        );
    }
}
