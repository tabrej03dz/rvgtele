<?php

namespace App\Http\Controllers;

use App\Models\CallDisposition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CallDispositionController extends Controller
{
    public function index(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $query = CallDisposition::query()->where('company_id', $companyId);
        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }
        $items = $query->latest()->paginate(20)->withQueryString();
        return view('modules.index', [
            'items' => $items,
            'title' => 'Call Dispositions',
            'routeName' => 'crm-settings.call-dispositions',
            'columns' => ['name', 'type', 'requires_follow_up', 'requires_remarks', 'is_active'],
        ]);
    }

    public function create()
    {
        return view('modules.form', [
            'item' => new CallDisposition(),
            'title' => 'Create Call Disposition',
            'routeName' => 'crm-settings.call-dispositions',
            'fields' => ['name' => 'text', 'type' => 'text', 'requires_follow_up' => 'checkbox', 'requires_remarks' => 'checkbox', 'is_active' => 'checkbox'],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['company_id'] = Auth::user()->company_id;
        $this->normalizeBooleans($data);
        CallDisposition::create($data);
        return redirect()->route('crm-settings.call-dispositions.index')->with('success', 'Call Disposition created successfully.');
    }

    public function edit(CallDisposition $item)
    {
        abort_unless((int) $item->company_id === (int) Auth::user()->company_id, 403);
        return view('modules.form', [
            'item' => $item,
            'title' => 'Edit Call Disposition',
            'routeName' => 'crm-settings.call-dispositions',
            'fields' => ['name' => 'text', 'type' => 'text', 'requires_follow_up' => 'checkbox', 'requires_remarks' => 'checkbox', 'is_active' => 'checkbox'],
        ]);
    }

    public function update(Request $request, CallDisposition $item)
    {
        abort_unless((int) $item->company_id === (int) Auth::user()->company_id, 403);
        $data = $this->validated($request);
        $this->normalizeBooleans($data);
        $item->update($data);
        return redirect()->route('crm-settings.call-dispositions.index')->with('success', 'Call Disposition updated successfully.');
    }

    public function destroy(CallDisposition $item)
    {
        abort_unless((int) $item->company_id === (int) Auth::user()->company_id, 403);
        $item->delete();
        return back()->with('success', 'Call Disposition deleted successfully.');
    }

    private function validated(Request $request): array
    {
        return $request->validate(['name' => ['nullable'], 'type' => ['nullable'], 'requires_follow_up' => ['nullable'], 'requires_remarks' => ['nullable'], 'is_active' => ['nullable']]);
    }

    private function normalizeBooleans(array &$data): void
    {
        foreach (['requires_follow_up', 'requires_remarks', 'is_active'] as $field) {
            $data[$field] = request()->boolean($field);
        }
    }
}
