<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Notifications\Notification;

class LowStockAlert extends Notification
{
    public function __construct(
        private readonly Product $product
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'product_id'   => $this->product->id,
            'product_name' => $this->product->display_name,
            'stock'        => (int) $this->product->stock_quantity,
            'min_stock'    => (int) $this->product->min_stock_quantity,
            'message'      => "Stock critique : « {$this->product->display_name} » ({$this->product->stock_quantity} restant(s), seuil {$this->product->min_stock_quantity}).",
            'url'          => route('products.show', $this->product),
        ];
    }
}
