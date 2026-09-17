<?php

namespace App\Observers;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ActivityLogObserver
{
    private const LABELS = [
        'Product'  => 'Produit',
        'Category' => 'Catégorie',
        'Unit'     => 'Unité',
        'Customer' => 'Client',
        'Supplier' => 'Fournisseur',
        'Sale'     => 'Vente',
        'Purchase' => 'Achat',
        'User'     => 'Utilisateur',
    ];

    // last_login_at est déjà couvert par l'entrée "login" du listener dédié :
    // l'ignorer ici évite un doublon à chaque connexion.
    private const IGNORED_ATTRIBUTES = ['updated_at', 'created_at', 'password', 'remember_token', 'last_login_at'];

    public function created(Model $model): void
    {
        $this->record('create', $model, sprintf('%s "%s" créé(e).', $this->typeLabel($model), $this->identify($model)));
    }

    public function updated(Model $model): void
    {
        $changes = Arr::except($model->getChanges(), self::IGNORED_ATTRIBUTES);
        if (empty($changes)) {
            return;
        }

        $fields = implode(', ', array_keys($changes));
        $this->record('update', $model, sprintf('%s "%s" modifié(e) (%s).', $this->typeLabel($model), $this->identify($model), $fields));
    }

    public function deleted(Model $model): void
    {
        $this->record('delete', $model, sprintf('%s "%s" supprimé(e).', $this->typeLabel($model), $this->identify($model)));
    }

    // Évite de polluer le journal avec les seeders/commandes artisan (ex: seed
    // rejoué à chaque déploiement dev) — seules les actions faites par un
    // utilisateur via une requête HTTP sont tracées.
    private function record(string $verb, Model $model, string $description): void
    {
        if (app()->runningInConsole()) {
            return;
        }

        $action = $verb . '_' . Str::snake(class_basename($model));
        ActivityLog::log($action, $description, $model);
    }

    private function identify(Model $model): string
    {
        foreach (['reference', 'name'] as $field) {
            if (! empty($model->{$field})) {
                return (string) $model->{$field};
            }
        }

        return "#{$model->id}";
    }

    private function typeLabel(Model $model): string
    {
        return self::LABELS[class_basename($model)] ?? class_basename($model);
    }
}
