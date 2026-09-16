<?php

namespace App\Observers;

use App\Models\Product;
use App\Models\User;
use App\Notifications\LowStockAlert;
use Illuminate\Support\Facades\Notification;

class ProductObserver
{
    /**
     * Alerte les responsables du stock dès qu'un produit franchit son seuil
     * critique (pour ne pas spammer une notification à chaque vente tant
     * qu'il reste sous le seuil).
     */
    public function updated(Product $product): void
    {
        if (!$product->is_active || !$product->wasChanged('stock_quantity')) {
            return;
        }

        $wasLow = (float) $product->getOriginal('stock_quantity') <= (float) $product->min_stock_quantity;

        if ($wasLow || !$product->isLowStock()) {
            return;
        }

        $recipients = User::permission('receive notifications')->where('is_active', true)->get();

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new LowStockAlert($product));
        }
    }
}
