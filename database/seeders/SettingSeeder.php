<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
  $settings = [
// Entreprise
['company_name',     'Espace Mokolo Obala',  'company'],
['company_address',  'Cameroun, Centre-Obala', 'company'],
['company_phone',    '+225 27 20 00 00 00','company'],
['company_email',    'contact@mokolo.com', 'company'],
['company_tax_id',   'xxx','company'],
['company_website',  '', 'company'],
['currency',   'XAF', 'company'],
['currency_symbol',  'XAF', 'company'],

// Factures
['invoice_prefix',   'FAC', 'invoice'],
['invoice_footer',   'Merci pour votre confiance — Bertrand Store',  'invoice'],
['invoice_note',     'Paiement à réception. Tout article vendu ne sera ni repris ni échangé.', 'invoice'],
['show_tax',   '1',   'invoice'],
['show_discount',    '0',   'invoice'],
['show_logo',  '1',   'invoice'],
['tax_rate_default', '18',  'invoice'],

// TVA
['tax_rates', json_encode([
 ['rate' => 0,   'label' => 'Exonéré (0%)','is_default' => false],
 ['rate' => 10,  'label' => 'TVA réduite (10%)', 'is_default' => false],
 ['rate' => 18,  'label' => 'TVA normale (18%)', 'is_default' => true],
]), 'tax'],
  ];

  foreach ($settings as [$key, $value, $group]) {
Setting::updateOrCreate(['key' => $key], ['value' => $value, 'group' => $group]);
  }
    }
}
