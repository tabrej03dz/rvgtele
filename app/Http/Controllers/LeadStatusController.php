<?php

namespace App\Http\Controllers;

use App\Models\LeadStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeadStatusController extends Controller
{
    public function index(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $query = LeadStatus::query()->where('company_id', $companyId);
        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }
        $items = $query->latest()->paginate(20)->withQueryString();
        return view('modules.index', [
            'items' => $items,
            'title' => 'Lead Statuses',
            'routeName' => 'crm-settings.lead-statuses',
            'columns' => ['name', 'slug', 'color', 'sort_order', 'is_converted', 'is_lost', 'is_active'],
        ]);
    }

    public function create()
    {
        return view('modules.form', [
            'item' => new LeadStatus(),
            'title' => 'Create Lead Statuse',
            'routeName' => 'crm-settings.lead-statuses',
            'fields' => ['name' => 'text', 'slug' => 'text', 'color' => 'color', 'sort_order' => 'number', 'is_converted' => 'checkbox', 'is_lost' => 'checkbox', 'is_active' => 'checkbox'],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['company_id'] = Auth::user()->company_id;
        $this->normalizeBooleans($data);
        LeadStatus::create($data);
        return redirect()->route('crm-settings.lead-statuses.index')->with('success', 'Lead Statuse created successfully.');
    }

    public function edit(LeadStatus $item)
    {
        abort_unless((int) $item->company_id === (int) Auth::user()->company_id, 403);
        return view('modules.form', [
            'item' => $item,
            'title' => 'Edit Lead Statuse',
            'routeName' => 'crm-settings.lead-statuses',
            'fields' => ['name' => 'text', 'slug' => 'text', 'color' => 'color', 'sort_order' => 'number', 'is_converted' => 'checkbox', 'is_lost' => 'checkbox', 'is_active' => 'checkbox'],
        ]);
    }

    public function update(Request $request, LeadStatus $item)
    {
        abort_unless((int) $item->company_id === (int) Auth::user()->company_id, 403);
        $data = $this->validated($request);
        $this->normalizeBooleans($data);
        $item->update($data);
        return redirect()->route('crm-settings.lead-statuses.index')->with('success', 'Lead Statuse updated successfully.');
    }

    public function destroy(LeadStatus $item)
    {
        abort_unless((int) $item->company_id === (int) Auth::user()->company_id, 403);
        $item->delete();
        return back()->with('success', 'Lead Statuse deleted successfully.');
    }

    private function validated(Request $request): array
    {
        return $request->validate(['name' => ['nullable'], 'slug' => ['nullable'], 'color' => ['nullable'], 'sort_order' => ['nullable'], 'is_converted' => ['nullable'], 'is_lost' => ['nullable'], 'is_active' => ['nullable']]);
    }

    private function normalizeBooleans(array &$data): void
    {
        foreach (['is_converted', 'is_lost', 'is_active'] as $field) {
            $data[$field] = request()->boolean($field);
        }
    }
}
