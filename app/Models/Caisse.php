<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Caisse extends Model
{
    protected $fillable = ['name', 'description', 'is_active', 'manager_id'];

    protected $casts = ['is_active' => 'boolean'];

    public function sessions(): HasMany { return $this->hasMany(PosSession::class); }
    public function manager(): BelongsTo { return $this->belongsTo(User::class, 'manager_id'); }
    public function authorizedUsers(): BelongsToMany { return $this->belongsToMany(User::class); }

    // Un utilisateur peut ouvrir une session sur cette caisse s'il en est le
    // gérant assigné, ou s'il est Admin/Super Admin. Sans gérant assigné
    // (manager_id null), seuls les Admin/Super Admin y ont accès.
    public function canBeOpenedBy(User $user): bool
    {
        return $user->hasAnyRole(['Admin', 'Super Admin'])
            || $this->manager_id === $user->id
            || $this->authorizedUsers()->whereKey($user->id)->exists();
    }
}
