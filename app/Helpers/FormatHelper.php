<?php

namespace App\Helpers;

class FormatHelper
{
    public static function money(float|string|null $amount, string $currency = null, int $decimals = 0): string
    {
        $currency  ??= config('app.currency_symbol', config('app.currency', 'XOF'));
        $formatted   = number_format((float) ($amount ?? 0), $decimals, ',', ' ');
        return $formatted . ' ' . $currency;
    }

    public static function date(\Carbon\Carbon|string|null $date, string $format = 'd/m/Y'): string
    {
        if (!$date) {
            return '—';
        }
        $carbon = $date instanceof \Carbon\Carbon ? $date : \Carbon\Carbon::parse($date);
        return $carbon->format($format);
    }

    public static function datetime(\Carbon\Carbon|string|null $date): string
    {
        return self::date($date, 'd/m/Y H:i');
    }

    public static function number(float|int $value, int $decimals = 0): string
    {
        return number_format($value, $decimals, ',', ' ');
    }

    public static function percent(float $value, int $decimals = 1): string
    {
        return number_format($value, $decimals, ',', ' ') . ' %';
    }

    public static function statusBadge(string $status): string
    {
        $map = [
            'ordered'    => ['Commandé',           'yellow'],
            'partial'    => ['Partiel',            'blue'],
            'delivered'  => ['Livré',              'green'],
            'pending'    => ['En attente',         'yellow'],
            'paid'       => ['Payé',               'green'],
            'draft'      => ['Brouillon',          'gray'],
            'confirmed'  => ['Confirmé',           'green'],
            'completed'  => ['Terminé',            'green'],
            'cancelled'  => ['Annulé',             'red'],
            'sent'       => ['Envoyé',             'blue'],
            'accepted'   => ['Accepté',            'green'],
            'rejected'   => ['Rejeté',             'red'],
            'expired'    => ['Expiré',             'red'],
            'in_transit' => ['En transit',         'blue'],
            'received'   => ['Reçu',               'green'],
        ];

        [$label, $color] = $map[$status] ?? [ucfirst($status), 'gray'];
        return '<span class="badge badge--' . $color . '">' . $label . '</span>';
    }
}
