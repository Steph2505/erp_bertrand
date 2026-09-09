<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseCategory extends Model
{
    protected $fillable = ['name', 'parent_id'];

    public function parent(): BelongsTo  { return $this->belongsTo(ExpenseCategory::class, 'parent_id'); }
    public function children(): HasMany  { return $this->hasMany(ExpenseCategory::class, 'parent_id'); }
    public function expenses(): HasMany  { return $this->hasMany(Expense::class, 'expense_category_id'); }
}
