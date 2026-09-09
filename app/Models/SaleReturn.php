<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleReturn extends Model
{
    protected $fillable = ['reference', 'sale_id', 'return_date', 'total', 'reason', 'created_by'];

    protected $casts = ['return_date' => 'date', 'total' => 'decimal:2'];

    public function sale(): BelongsTo      { return $this->belongsTo(Sale::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
