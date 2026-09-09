<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id', 'action', 'model_type', 'model_id', 'description', 'ip_address',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }

    public static function log(string $action, string $description, ?Model $model = null): void
    {
        static::create([
            'user_id'     => auth()->id(),
            'action'      => $action,
            'model_type'  => $model ? get_class($model) : null,
            'model_id'    => $model?->id,
            'description' => $description,
            'ip_address'  => request()->ip(),
        ]);
    }
}
