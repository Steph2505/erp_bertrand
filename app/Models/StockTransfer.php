<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockTransfer extends Model
{
    protected $fillable = [
        'reference', 'from_warehouse_id', 'to_warehouse_id',
        'transfer_date', 'status', 'note', 'created_by',
    ];

    protected $casts = ['transfer_date' => 'date'];

    public function fromWarehouse(): BelongsTo { return $this->belongsTo(Warehouse::class, 'from_warehouse_id'); }
    public function toWarehouse(): BelongsTo   { return $this->belongsTo(Warehouse::class, 'to_warehouse_id'); }
    public function createdBy(): BelongsTo     { return $this->belongsTo(User::class, 'created_by'); }
    public function items(): HasMany           { return $this->hasMany(StockTransferItem::class); }
}
