<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $companyId = Auth::user()->company_id;
        $query = Product::query()->where('company_id', $companyId);
        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }
        $items = $query->latest()->paginate(20)->withQueryString();
        return view('modules.index', [
            'items' => $items,
            'title' => 'Products',
            'routeName' => 'products',
            'columns' => ['name', 'code', 'description', 'base_price', 'tax_percent', 'is_active'],
        ]);
    }

    public function create()
    {
        return view('modules.form', [
            'item' => new Product(),
            'title' => 'Create Product',
            'routeName' => 'products',
            'fields' => ['name' => 'text', 'code' => 'text', 'description' => 'textarea', 'base_price' => 'number', 'tax_percent' => 'number', 'is_active' => 'checkbox'],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['company_id'] = Auth::user()->company_id;
        $this->normalizeBooleans($data);
        Product::create($data);
        return redirect()->route('products.index')->with('success', 'Product created successfully.');
    }

    public function edit(Product $item)
    {
        abort_unless((int) $item->company_id === (int) Auth::user()->company_id, 403);
        return view('modules.form', [
            'item' => $item,
            'title' => 'Edit Product',
            'routeName' => 'products',
            'fields' => ['name' => 'text', 'code' => 'text', 'description' => 'textarea', 'base_price' => 'number', 'tax_percent' => 'number', 'is_active' => 'checkbox'],
        ]);
    }

    public function update(Request $request, Product $item)
    {
        abort_unless((int) $item->company_id === (int) Auth::user()->company_id, 403);
        $data = $this->validated($request);
        $this->normalizeBooleans($data);
        $item->update($data);
        return redirect()->route('products.index')->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $item)
    {
        abort_unless((int) $item->company_id === (int) Auth::user()->company_id, 403);
        $item->delete();
        return back()->with('success', 'Product deleted successfully.');
    }

    private function validated(Request $request): array
    {
        return $request->validate(['name' => ['nullable'], 'code' => ['nullable'], 'description' => ['nullable'], 'base_price' => ['nullable'], 'tax_percent' => ['nullable'], 'is_active' => ['nullable']]);
    }

    private function normalizeBooleans(array &$data): void
    {
        foreach (['is_active'] as $field) {
            $data[$field] = request()->boolean($field);
        }
    }
}
