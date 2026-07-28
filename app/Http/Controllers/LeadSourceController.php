<?php

namespace App\Http\Controllers;

use App\Models\LeadSource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeadSourceController extends Controller
{
    public function index(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $query = LeadSource::query()->where('company_id', $companyId);
        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }
        $items = $query->latest()->paginate(20)->withQueryString();
        return view('modules.index', [
            'items' => $items,
            'title' => 'Lead Sources',
            'routeName' => 'crm-settings.lead-sources',
            'columns' => ['name', 'is_active'],
        ]);
    }

    public function create()
    {
        return view('modules.form', [
            'item' => new LeadSource(),
            'title' => 'Create Lead Source',
            'routeName' => 'crm-settings.lead-sources',
            'fields' => ['name' => 'text', 'is_active' => 'checkbox'],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['company_id'] = Auth::user()->company_id;
        $this->normalizeBooleans($data);
        LeadSource::create($data);
        return redirect()->route('crm-settings.lead-sources.index')->with('success', 'Lead Source created successfully.');
    }

    public function edit(LeadSource $item)
    {
        abort_unless((int) $item->company_id === (int) Auth::user()->company_id, 403);
        return view('modules.form', [
            'item' => $item,
            'title' => 'Edit Lead Source',
            'routeName' => 'crm-settings.lead-sources',
            'fields' => ['name' => 'text', 'is_active' => 'checkbox'],
        ]);
    }

    public function update(Request $request, LeadSource $item)
    {
        abort_unless((int) $item->company_id === (int) Auth::user()->company_id, 403);
        $data = $this->validated($request);
        $this->normalizeBooleans($data);
        $item->update($data);
        return redirect()->route('crm-settings.lead-sources.index')->with('success', 'Lead Source updated successfully.');
    }

    public function destroy(LeadSource $item)
    {
        abort_unless((int) $item->company_id === (int) Auth::user()->company_id, 403);
        $item->delete();
        return back()->with('success', 'Lead Source deleted successfully.');
    }

    private function validated(Request $request): array
    {
        return $request->validate(['name' => ['nullable'], 'is_active' => ['nullable']]);
    }

    private function normalizeBooleans(array &$data): void
    {
        foreach (['is_active'] as $field) {
            $data[$field] = request()->boolean($field);
        }
    }
}
