<?php

namespace App\Helpers;

use App\Models\SaleItem;
use Illuminate\Support\Facades\DB;

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
        $salesCost = (float) SaleItem::join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->whereIn('sales.status', ['confirmed', 'completed'])
            ->whereDate('sales.sale_date', '>=', $from)
            ->whereDate('sales.sale_date', '<=', $to)
            ->whereNull('sales.deleted_at')
            ->selectRaw("COALESCE(SUM(
                CASE
                    WHEN sale_items.item_type = 'pack' THEN sale_items.quantity * sale_items.unit_cost
                    ELSE sale_items.quantity * sale_items.units_per_item * sale_items.unit_cost
                END
            ), 0) as total")
            ->value('total');

        return $salesCost - self::returnedProductCogs($from, $to);
    }

    public static function returnedProductCogs(string $from, string $to, ?int $hour = null): float
    {
        return (float) DB::table('sale_return_items')
            ->join('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
            ->join('sale_items', 'sale_items.id', '=', 'sale_return_items.sale_item_id')
            ->join('sales', 'sales.id', '=', 'sale_returns.sale_id')
            ->where('sale_return_items.stock_condition', 'resellable')
            ->whereDate('sale_returns.return_date', '>=', $from)
            ->whereDate('sale_returns.return_date', '<=', $to)
            ->whereNull('sales.deleted_at')
            ->when($hour !== null, fn ($query) => $query->whereRaw('HOUR(sale_returns.created_at) = ?', [$hour]))
            ->selectRaw("COALESCE(SUM(COALESCE(
                sale_return_items.cost_total,
                CASE
                    WHEN sale_items.item_type = 'pack' THEN sale_return_items.quantity * sale_items.unit_cost
                    ELSE sale_return_items.quantity * sale_items.units_per_item * sale_items.unit_cost
                END
            )), 0) as total")
            ->value('total');
    }
}
