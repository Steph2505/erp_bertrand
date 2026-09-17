<?php

namespace App\Helpers;

use App\Models\SaleItem;

class ProfitHelper
{
    /**
     * Coût réel des produits vendus (POS + Vente confondus) sur la période,
     * calculé ligne par ligne à partir du coût figé au moment de la vente
     * (unit_cost) — reste exact même si le prix d'achat du produit change
     * ensuite. Utilisé par le rapport Profit/Perte et le tableau de bord,
     * pour que les deux affichent le même bénéfice net.
     */
    public static function productCogs(string $from, string $to): float
    {
        return (float) SaleItem::join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereIn('sales.status', ['confirmed', 'completed'])
            ->whereBetween('sales.sale_date', [$from, $to])
            ->whereNull('sales.deleted_at')
            ->selectRaw("COALESCE(SUM(
                CASE
                    WHEN sale_items.item_type = 'pack' THEN sale_items.quantity * sale_items.unit_cost
                    ELSE sale_items.quantity * sale_items.units_per_item * sale_items.unit_cost
                END
            ), 0) as total")
            ->value('total');
    }
}
