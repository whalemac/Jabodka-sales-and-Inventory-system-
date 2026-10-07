<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection as SupportCollection;

class ConsignmentItem extends Model
{
    protected $fillable = [
        'consignment_receipt_id',
        'partner_id',
        'variant_id',
        'units_delivered',
        'agreed_base_price',
        'shop_markup',
        'received_at',
    ];

    protected function casts(): array
    {
        return [
            'units_delivered' => 'integer',
            'agreed_base_price' => 'decimal:2',
            'shop_markup' => 'decimal:2',
            'received_at' => 'datetime',
        ];
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(ConsignmentPartner::class, 'partner_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function paymentItems(): HasMany
    {
        return $this->hasMany(ConsignmentPaymentItem::class);
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(ConsignmentReceipt::class, 'consignment_receipt_id');
    }

    public function pullouts(): HasMany
    {
        return $this->hasMany(ConsignmentPullout::class);
    }

    public function sellingPrice(): float
    {
        return (float) $this->agreed_base_price + (float) $this->shop_markup;
    }

    public function unitsPulled(): int
    {
        if ($this->relationLoaded('pullouts')) {
            return (int) $this->pullouts->sum('quantity_pulled');
        }

        return (int) $this->pullouts()->sum('quantity_pulled');
    }

    public function unitsSoldAllocated(): int
    {
        return (int) ($this->units_sold_allocated ?? 0);
    }

    public function remainingQuantity(): int
    {
        return max(0, (int) $this->units_delivered - $this->unitsSoldAllocated() - $this->unitsPulled());
    }

    public function isFullyPulledOut(): bool
    {
        return $this->remainingQuantity() === 0 && $this->unitsPulled() > 0;
    }

    /**
     * FIFO-allocate sold units across consignment lots of the same variant.
     *
     * @param  Collection<int, self>|SupportCollection<int, self>  $items
     */
    public static function allocateSoldQuantities(Collection|SupportCollection $items): void
    {
        if ($items->isEmpty()) {
            return;
        }

        $variantIds = $items->pluck('variant_id')->unique()->values();

        $soldByVariant = SalesItem::query()
            ->whereIn('variant_id', $variantIds)
            ->selectRaw('variant_id, SUM(quantity) as qty')
            ->groupBy('variant_id')
            ->pluck('qty', 'variant_id');

        $lots = static::query()
            ->whereIn('variant_id', $variantIds)
            ->orderBy('received_at')
            ->orderBy('id')
            ->get()
            ->groupBy('variant_id');

        $soldOnLot = [];
        foreach ($lots as $variantId => $group) {
            $remainingSold = (int) ($soldByVariant[$variantId] ?? 0);
            foreach ($group as $lot) {
                $take = min((int) $lot->units_delivered, $remainingSold);
                $soldOnLot[$lot->id] = $take;
                $remainingSold -= $take;
            }
        }

        foreach ($items as $item) {
            $item->units_sold_allocated = $soldOnLot[$item->id] ?? 0;
        }
    }
}
