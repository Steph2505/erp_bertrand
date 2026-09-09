<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseReturn extends Model
{
    protected $fillable = ['reference', 'purchase_id', 'return_date', 'total', 'reason', 'created_by'];

    protected $casts = ['return_date' => 'date', 'total' => 'decimal:2'];

    public function purchase(): BelongsTo  { return $this->belongsTo(Purchase::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
