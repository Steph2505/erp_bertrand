<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountTransfer extends Model
{
    protected $fillable = [
        'reference', 'from_account_id', 'to_account_id', 'amount', 'transfer_date', 'note', 'created_by',
    ];

    protected $casts = [
        'transfer_date' => 'date',
        'amount'        => 'decimal:2',
    ];

    public function fromAccount(): BelongsTo { return $this->belongsTo(PaymentAccount::class, 'from_account_id'); }
    public function toAccount(): BelongsTo   { return $this->belongsTo(PaymentAccount::class, 'to_account_id'); }
    public function createdBy(): BelongsTo   { return $this->belongsTo(User::class, 'created_by'); }
}
