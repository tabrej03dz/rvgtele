<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CampaignController extends Controller
{
    public function index(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $query = Campaign::query()->where('company_id', $companyId);
        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }
        $items = $query->latest()->paginate(20)->withQueryString();
        return view('modules.index', [
            'items' => $items,
            'title' => 'Campaigns',
            'routeName' => 'campaigns',
            'columns' => ['name', 'code', 'start_date', 'end_date', 'target_calls', 'target_conversions', 'budget', 'status', 'description'],
        ]);
    }

    public function create()
    {
        return view('modules.form', [
            'item' => new Campaign(),
            'title' => 'Create Campaign',
            'routeName' => 'campaigns',
            'fields' => ['name' => 'text', 'code' => 'text', 'start_date' => 'date', 'end_date' => 'date', 'target_calls' => 'number', 'target_conversions' => 'number', 'budget' => 'number', 'status' => 'text', 'description' => 'textarea'],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['company_id'] = Auth::user()->company_id;
        $this->normalizeBooleans($data);
        Campaign::create($data);
        return redirect()->route('campaigns.index')->with('success', 'Campaign created successfully.');
    }

    public function edit(Campaign $item)
    {
        abort_unless((int) $item->company_id === (int) Auth::user()->company_id, 403);
        return view('modules.form', [
            'item' => $item,
            'title' => 'Edit Campaign',
            'routeName' => 'campaigns',
            'fields' => ['name' => 'text', 'code' => 'text', 'start_date' => 'date', 'end_date' => 'date', 'target_calls' => 'number', 'target_conversions' => 'number', 'budget' => 'number', 'status' => 'text', 'description' => 'textarea'],
        ]);
    }

    public function update(Request $request, Campaign $item)
    {
        abort_unless((int) $item->company_id === (int) Auth::user()->company_id, 403);
        $data = $this->validated($request);
        $this->normalizeBooleans($data);
        $item->update($data);
        return redirect()->route('campaigns.index')->with('success', 'Campaign updated successfully.');
    }

    public function destroy(Campaign $item)
    {
        abort_unless((int) $item->company_id === (int) Auth::user()->company_id, 403);
        $item->delete();
        return back()->with('success', 'Campaign deleted successfully.');
    }

    private function validated(Request $request): array
    {
        return $request->validate(['name' => ['nullable'], 'code' => ['nullable'], 'start_date' => ['nullable'], 'end_date' => ['nullable'], 'target_calls' => ['nullable'], 'target_conversions' => ['nullable'], 'budget' => ['nullable'], 'status' => ['nullable'], 'description' => ['nullable']]);
    }

    private function normalizeBooleans(array &$data): void
    {
        foreach ([] as $field) {
            $data[$field] = request()->boolean($field);
        }
    }
}
