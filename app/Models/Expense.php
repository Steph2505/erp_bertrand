<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'reference', 'expense_category_id', 'payment_account_id',
        'expense_date', 'amount', 'description', 'attachment', 'created_by',
    ];

    protected $casts = ['expense_date' => 'date', 'amount' => 'decimal:2'];

    public function category(): BelongsTo       { return $this->belongsTo(ExpenseCategory::class, 'expense_category_id'); }
    public function paymentAccount(): BelongsTo { return $this->belongsTo(PaymentAccount::class); }
    public function createdBy(): BelongsTo      { return $this->belongsTo(User::class, 'created_by'); }
}
