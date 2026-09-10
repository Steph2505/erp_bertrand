<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class SettingController extends Controller
{
    // ── Entreprise ────────────────────────────────────────────────────────────

    public function company(): View|RedirectResponse
    {
        try {
            $settings = Setting::where('group', 'company')->pluck('value', 'key');
            return view('pages.settings.company', compact('settings'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement des paramètres entreprise');
            return redirect()->route('dashboard')->with('error', 'Une erreur est survenue lors du chargement de la page.');
        }
    }

    public function saveCompany(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_name'    => 'required|string|max:191',
            'company_email'   => 'nullable|email',
            'company_phone'   => 'nullable|string|max:30',
            'company_address' => 'nullable|string|max:500',
            'company_tax_id'  => 'nullable|string|max:100',
            'company_website' => 'nullable|url|max:191',
            'currency'        => 'nullable|string|max:10',
            'currency_symbol' => 'nullable|string|max:10',
        ]);

        try {
            foreach ($data as $key => $value) {
                Setting::set($key, $value, 'company');
            }
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la sauvegarde des paramètres entreprise', ['data' => $data]);
            return back()->withInput()->with('error', 'Une erreur est survenue lors de la sauvegarde.');
        }

        return back()->with('success', 'Paramètres entreprise sauvegardés.');
    }

    // ── Entrepôts ─────────────────────────────────────────────────────────────

    public function warehouses(): View|RedirectResponse
    {
        try {
            $warehouses         = Warehouse::orderBy('name')->get();
            $defaultWarehouseId = (int) Setting::get('default_warehouse_id');
            return view('pages.settings.warehouses', compact('warehouses', 'defaultWarehouseId'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement des entrepôts');
            return redirect()->route('dashboard')->with('error', 'Une erreur est survenue lors du chargement de la page.');
        }
    }

    public function setDefaultWarehouse(Warehouse $warehouse): RedirectResponse
    {
        try {
            Setting::set('default_warehouse_id', $warehouse->id, 'general');
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la définition du magasin par défaut', ['warehouse_id' => $warehouse->id]);
            return back()->with('error', 'Une erreur est survenue.');
        }

        return back()->with('success', "« {$warehouse->name} » défini comme magasin par défaut.");
    }

    public function storeWarehouse(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'    => 'required|string|max:191',
            'code'    => 'required|string|max:20|unique:warehouses,code',
            'address' => 'nullable|string|max:500',
            'phone'   => 'nullable|string|max:30',
        ]);

        try {
            Warehouse::create(array_merge($data, ['is_active' => true]));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la création de l\'entrepôt', ['data' => $data]);
            return back()->withInput()->with('error', 'Une erreur est survenue lors de la création de l\'entrepôt.');
        }

        return back()->with('success', 'Entrepôt créé.');
    }

    public function updateWarehouse(Request $request, Warehouse $warehouse): RedirectResponse
    {
        $data = $request->validate([
            'name'      => 'required|string|max:191',
            'address'   => 'nullable|string|max:500',
            'phone'     => 'nullable|string|max:30',
            'is_active' => 'boolean',
        ]);

        try {
            $warehouse->update($data);
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la mise à jour de l\'entrepôt', ['warehouse_id' => $warehouse->id, 'data' => $data]);
            return back()->withInput()->with('error', 'Une erreur est survenue lors de la mise à jour de l\'entrepôt.');
        }

        return back()->with('success', 'Entrepôt mis à jour.');
    }

    public function destroyWarehouse(Warehouse $warehouse): RedirectResponse
    {
        $linked = \App\Models\Sale::where('warehouse_id', $warehouse->id)->exists()
               || \App\Models\Purchase::where('warehouse_id', $warehouse->id)->exists();

        if ($linked) {
            return back()->withErrors(['warehouse' => 'Impossible : cet entrepôt est associé à des ventes ou des achats.']);
        }

        try {
            $warehouse->delete();
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la suppression de l\'entrepôt', ['warehouse_id' => $warehouse->id]);
            return back()->with('error', 'Une erreur est survenue lors de la suppression de l\'entrepôt.');
        }

        return back()->with('success', 'Entrepôt supprimé.');
    }

    // ── Factures ──────────────────────────────────────────────────────────────

    public function invoices(): View|RedirectResponse
    {
        try {
            $settings = Setting::where('group', 'invoice')->pluck('value', 'key');
            return view('pages.settings.invoices', compact('settings'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement des paramètres factures');
            return redirect()->route('dashboard')->with('error', 'Une erreur est survenue lors du chargement de la page.');
        }
    }

    public function saveInvoices(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'invoice_prefix'   => 'nullable|string|max:20',
            'invoice_footer'   => 'nullable|string|max:1000',
            'invoice_note'     => 'nullable|string|max:1000',
            'show_tax'         => 'boolean',
            'show_discount'    => 'boolean',
            'show_logo'        => 'boolean',
            'tax_rate_default' => 'nullable|numeric|min:0|max:100',
        ]);

        try {
            foreach ($data as $key => $value) {
                Setting::set($key, $value, 'invoice');
            }
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la sauvegarde des paramètres factures', ['data' => $data]);
            return back()->withInput()->with('error', 'Une erreur est survenue lors de la sauvegarde.');
        }

        return back()->with('success', 'Paramètres factures sauvegardés.');
    }

    // ── Taux de TVA ───────────────────────────────────────────────────────────

    public function taxRates(): View|RedirectResponse
    {
        try {
            $raw   = Setting::get('tax_rates', null);
            $rates = $raw ? json_decode($raw, true) : [
                ['rate' => 0,    'label' => 'Exonéré (0%)',     'is_default' => false],
                ['rate' => 10,   'label' => 'TVA réduite (10%)', 'is_default' => false],
                ['rate' => 18,   'label' => 'TVA normale (18%)', 'is_default' => true],
            ];

            return view('pages.settings.tax-rates', compact('rates'));
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors du chargement des taux de TVA');
            return redirect()->route('dashboard')->with('error', 'Une erreur est survenue lors du chargement de la page.');
        }
    }

    public function saveTaxRates(Request $request): RedirectResponse
    {
        $request->validate([
            'rates'              => 'required|array|min:1',
            'rates.*.rate'       => 'required|numeric|min:0|max:100',
            'rates.*.label'      => 'required|string|max:100',
            'rates.*.is_default' => 'nullable|boolean',
        ]);

        try {
            $rates = collect($request->rates)->map(fn($r, $i) => [
                'rate'       => (float) $r['rate'],
                'label'      => trim($r['label']),
                'is_default' => (bool) ($r['is_default'] ?? false),
            ])->values()->toArray();

            // Un seul taux par défaut
            $hasDefault = collect($rates)->contains('is_default', true);
            if (! $hasDefault && count($rates) > 0) {
                $rates[0]['is_default'] = true;
            }

            Setting::set('tax_rates', json_encode($rates), 'tax');

            // Synchroniser aussi le taux par défaut dans les paramètres factures
            $default = collect($rates)->firstWhere('is_default', true);
            if ($default) {
                Setting::set('tax_rate_default', $default['rate'], 'invoice');
            }
        } catch (Throwable $e) {
            $this->logError($e, 'Erreur lors de la sauvegarde des taux de TVA', ['rates' => $request->rates]);
            return back()->withInput()->with('error', 'Une erreur est survenue lors de la sauvegarde des taux.');
        }

        return back()->with('success', 'Taux de TVA sauvegardés.');
    }
}
