<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BranchController extends Controller
{
    public function index(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $query = Branch::query()->where('company_id', $companyId);
        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }
        $items = $query->latest()->paginate(20)->withQueryString();
        return view('modules.index', [
            'items' => $items,
            'title' => 'Branches',
            'routeName' => 'branches',
            'columns' => ['name', 'code', 'phone', 'address', 'is_active'],
        ]);
    }

    public function create()
    {
        return view('modules.form', [
            'item' => new Branch(),
            'title' => 'Create Branche',
            'routeName' => 'branches',
            'fields' => ['name' => 'text', 'code' => 'text', 'phone' => 'text', 'address' => 'textarea', 'is_active' => 'checkbox'],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['company_id'] = Auth::user()->company_id;
        $this->normalizeBooleans($data);
        Branch::create($data);
        return redirect()->route('branches.index')->with('success', 'Branche created successfully.');
    }

    public function edit(Branch $item)
    {
        abort_unless((int) $item->company_id === (int) Auth::user()->company_id, 403);
        return view('modules.form', [
            'item' => $item,
            'title' => 'Edit Branche',
            'routeName' => 'branches',
            'fields' => ['name' => 'text', 'code' => 'text', 'phone' => 'text', 'address' => 'textarea', 'is_active' => 'checkbox'],
        ]);
    }

    public function update(Request $request, Branch $item)
    {
        abort_unless((int) $item->company_id === (int) Auth::user()->company_id, 403);
        $data = $this->validated($request);
        $this->normalizeBooleans($data);
        $item->update($data);
        return redirect()->route('branches.index')->with('success', 'Branche updated successfully.');
    }

    public function destroy(Branch $item)
    {
        abort_unless((int) $item->company_id === (int) Auth::user()->company_id, 403);
        $item->delete();
        return back()->with('success', 'Branche deleted successfully.');
    }

    private function validated(Request $request): array
    {
        return $request->validate(['name' => ['nullable'], 'code' => ['nullable'], 'phone' => ['nullable'], 'address' => ['nullable'], 'is_active' => ['nullable']]);
    }

    private function normalizeBooleans(array &$data): void
    {
        foreach (['is_active'] as $field) {
            $data[$field] = request()->boolean($field);
        }
    }
}
