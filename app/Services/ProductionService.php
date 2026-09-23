<?php

namespace App\Services;

use App\Models\MaterialTransaction;
use App\Models\ProductionLog;
use App\Models\RawMaterial;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProductionService
{
    public function __construct(protected StockService $stock) {}

    /**
     * @param  array<int, array{material_id:int, quantity:float}>  $materialsUsed
     */
    public function logProduction(User $user, array $data, array $materialsUsed = []): ProductionLog
    {
        return DB::transaction(function () use ($user, $data, $materialsUsed) {
            $log = ProductionLog::query()->create([
                'variant_id' => $data['variant_id'],
                'user_id' => $user->id,
                'quantity_produced' => $data['quantity_produced'],
                'production_date' => $data['production_date'],
                'notes' => $data['notes'] ?? null,
            ]);

            $this->stock->increment($log->variant, (int) $data['quantity_produced']);

            foreach ($materialsUsed as $used) {
                $qty = (float) $used['quantity'];
                if ($qty <= 0) {
                    continue;
                }

                $material = RawMaterial::query()->lockForUpdate()->findOrFail($used['material_id']);
                if ((float) $material->stock_quantity < $qty) {
                    throw new RuntimeException('Not enough '.$material->material_name.' in stock.');
                }

                $material->decrement('stock_quantity', $qty);

                MaterialTransaction::query()->create([
                    'material_id' => $material->id,
                    'production_id' => $log->id,
                    'user_id' => $user->id,
                    'transaction_type' => 'used',
                    'quantity' => $qty,
                    'logged_at' => now(),
                ]);
            }

            ActivityLogger::log('production.create', $log, $data['notes'] ?? null, $data, $user);

            return $log->load(['variant.product', 'materialTransactions.material']);
        });
    }

    public function receiveMaterial(User $user, int $materialId, float $quantity): MaterialTransaction
    {
        return DB::transaction(function () use ($user, $materialId, $quantity) {
            $material = RawMaterial::query()->lockForUpdate()->findOrFail($materialId);
            $material->increment('stock_quantity', $quantity);

            $tx = MaterialTransaction::query()->create([
                'material_id' => $material->id,
                'production_id' => null,
                'user_id' => $user->id,
                'transaction_type' => 'received',
                'quantity' => $quantity,
                'logged_at' => now(),
            ]);

            ActivityLogger::log('material.receive', $tx, 'Raw material delivery', [
                'quantity' => $quantity,
            ], $user);

            return $tx;
        });
    }
}
