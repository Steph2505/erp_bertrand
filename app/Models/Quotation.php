<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quotation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'reference', 'customer_id', 'quotation_date', 'expiry_date',
        'status', 'subtotal', 'discount', 'tax_amount', 'total', 'note', 'created_by',
    ];

    protected $casts = [
        'quotation_date' => 'date',
        'expiry_date'    => 'date',
        'subtotal'       => 'decimal:2',
        'discount'       => 'decimal:2',
        'tax_amount'     => 'decimal:2',
        'total'          => 'decimal:2',
    ];

    public function customer(): BelongsTo   { return $this->belongsTo(Customer::class); }
    public function createdBy(): BelongsTo  { return $this->belongsTo(User::class, 'created_by'); }
    public function items(): HasMany        { return $this->hasMany(QuotationItem::class); }

    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast() && $this->status === 'sent';
    }

    public static function statusLabel(string $status): string
    {
        return match($status) {
            'draft'    => 'Brouillon',
            'sent'     => 'Envoyé',
            'accepted' => 'Accepté',
            'rejected' => 'Refusé',
            'expired'  => 'Expiré',
            default    => $status,
        };
    }

    public static function statusBadgeClass(string $status): string
    {
        return match($status) {
            'accepted' => 'badge--green',
            'sent'     => 'badge--blue',
            'rejected' => 'badge--red',
            'expired'  => 'badge--red',
            default    => 'badge--gray',
        };
    }
}
