<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockAdjustment extends Model
{
    protected $fillable = [
        'reference', 'warehouse_id', 'adjustment_date',
        'type', 'reason', 'created_by',
    ];

    protected $casts = ['adjustment_date' => 'date'];

    public function warehouse(): BelongsTo  { return $this->belongsTo(Warehouse::class); }
    public function createdBy(): BelongsTo  { return $this->belongsTo(User::class, 'created_by'); }
    public function movements(): HasMany    { return $this->hasMany(StockMovement::class, 'reference', 'reference'); }
}
