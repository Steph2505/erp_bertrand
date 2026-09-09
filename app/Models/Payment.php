<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Payment extends Model
{
    protected $fillable = [
        'reference', 'payable_type', 'payable_id',
        'payment_account_id', 'amount', 'payment_date', 'payment_method', 'note', 'created_by',
    ];

    protected $casts = ['payment_date' => 'date', 'amount' => 'decimal:2'];

    public function payable(): MorphTo           { return $this->morphTo(); }
    public function paymentAccount(): BelongsTo  { return $this->belongsTo(PaymentAccount::class); }
    public function createdBy(): BelongsTo       { return $this->belongsTo(User::class, 'created_by'); }
}
