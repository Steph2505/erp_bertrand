<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionAgent extends Model
{
    protected $fillable = ['name', 'user_id', 'commission_rate', 'is_active'];

    protected $casts = ['is_active' => 'boolean', 'commission_rate' => 'decimal:2'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
