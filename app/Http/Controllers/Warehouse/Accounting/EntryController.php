<?php

namespace App\Http\Controllers\Warehouse\Accounting;

use App\Http\Controllers\Warehouse\BaseController;
use App\Models\AccountingEntry;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EntryController extends BaseController
{
    public function create()
    {
        $this->authorize('accounting.create');

        return Inertia::render('warehouse/accounting/Create', [
            'categories' => $this->categories(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('accounting.create');

        $validated = $request->validate([
            'date' => 'required|date',
            'type' => 'required|in:income,expense',
            'category' => 'required|string',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $validated['created_by'] = auth()->id();

        AccountingEntry::create($validated);

        return redirect()->route('warehouse.accounting.index')
            ->with('success', __('accounting.messages.created'));
    }

    public function edit(AccountingEntry $accounting_entry)
    {
        $this->authorize('accounting.edit');

        return Inertia::render('warehouse/accounting/Edit', [
            'entry' => $accounting_entry,
            'categories' => $this->categories(),
        ]);
    }

    public function update(Request $request, AccountingEntry $accounting_entry)
    {
        $this->authorize('accounting.edit');

        $validated = $request->validate([
            'date' => 'required|date',
            'category' => 'required|string',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $accounting_entry->update($validated);

        return redirect()->route('warehouse.accounting.index')
            ->with('success', __('accounting.messages.updated'));
    }

    public function destroy(AccountingEntry $accounting_entry)
    {
        $this->authorize('accounting.delete');

        $accounting_entry->delete();

        return redirect()->route('warehouse.accounting.index')
            ->with('success', __('accounting.messages.deleted'));
    }

    private function categories(): array
    {
        return [
            'income' => [
                'sales' => 'accounting.categories.sales',
                'service' => 'accounting.categories.service',
                'wallet_input' => 'accounting.categories.walletInput',
                'other' => 'accounting.categories.other',
            ],
            'expense' => [
                'materials' => 'accounting.categories.materials',
                'salaries' => 'accounting.categories.salaries',
                'utilities' => 'accounting.categories.utilities',
                'transport' => 'accounting.categories.transport',
                'wallet_output' => 'accounting.categories.walletOutput',
                'other' => 'accounting.categories.other',
            ],
        ];
    }
}
