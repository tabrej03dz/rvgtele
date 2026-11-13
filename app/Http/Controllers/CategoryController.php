<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    /**
     * Category Listing With Lead Count
     */
    public function index(Request $request): View
    {
        $companyId = $this->companyId($request);

        $search = trim((string) $request->get('search', ''));

        $categories = Category::query()
            ->where('categories.company_id', $companyId)

            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where(
                        'categories.name',
                        'like',
                        "%{$search}%"
                    )->orWhere(
                        'categories.description',
                        'like',
                        "%{$search}%"
                    );
                });
            })

            ->select('categories.*')

            // Count leads using category_id, not name
            ->selectSub(
                DB::table('leads')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn(
                        'leads.category_id',
                        'categories.id'
                    ),
                'leads_count'
            )

            ->orderByDesc('categories.id')

            ->paginate(20)

            ->withQueryString();

        return view('categories.index', [
            'title' => 'Categories',
            'categories' => $categories,
        ]);
    }

    /**
     * Create Category Page
     */
    public function create(Request $request): View
    {
        return view('categories.create', [
            'title' => 'Add Category',
        ]);
    }

    /**
     * Store Category
     */
    public function store(Request $request): RedirectResponse
    {
        $companyId = $this->companyId($request);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',

                Rule::unique('categories', 'name')
                    ->where(function ($query) use ($companyId) {
                        return $query->where(
                            'company_id',
                            $companyId
                        );
                    }),
            ],

            'description' => [
                'nullable',
                'string',
                'max:255',
            ],
        ], [
            'name.required' => 'Category name is required.',
            'name.unique' => 'This category already exists.',
            'name.max' => 'Category name cannot exceed 255 characters.',
            'description.max' => 'Description cannot exceed 255 characters.',
        ]);

        Category::create([
            'company_id' => $companyId,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()
            ->route('categories.index')
            ->with('success', 'Category created successfully.');
    }

    /**
     * Show Single Category
     */
    public function show(
        Request $request,
        Category $category
    ): View {
        $this->ensureCategoryBelongsToCompany(
            $request,
            $category
        );

        $category->setAttribute(
            'leads_count',
            DB::table('leads')
                ->where('category_id', $category->id)
                ->count()
        );

        return view('categories.show', [
            'title' => 'Category Details',
            'category' => $category,
        ]);
    }

    /**
     * Edit Category Page
     */
    public function edit(
        Request $request,
        Category $category
    ): View {
        $this->ensureCategoryBelongsToCompany(
            $request,
            $category
        );

        return view('categories.edit', [
            'title' => 'Edit Category',
            'category' => $category,
        ]);
    }

    /**
     * Update Category
     */
    public function update(
        Request $request,
        Category $category
    ): RedirectResponse {
        $this->ensureCategoryBelongsToCompany(
            $request,
            $category
        );

        $companyId = $this->companyId($request);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',

                Rule::unique('categories', 'name')
                    ->ignore($category->id)
                    ->where(function ($query) use ($companyId) {
                        return $query->where(
                            'company_id',
                            $companyId
                        );
                    }),
            ],

            'description' => [
                'nullable',
                'string',
                'max:255',
            ],
        ], [
            'name.required' => 'Category name is required.',
            'name.unique' => 'This category already exists.',
            'name.max' => 'Category name cannot exceed 255 characters.',
            'description.max' => 'Description cannot exceed 255 characters.',
        ]);

        $category->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()
            ->route('categories.index')
            ->with('success', 'Category updated successfully.');
    }

    /**
     * Delete Category
     */
    public function destroy(
        Request $request,
        Category $category
    ): RedirectResponse {
        $this->ensureCategoryBelongsToCompany(
            $request,
            $category
        );

        // Prevent deletion of categories containing leads
        $hasLeads = DB::table('leads')
            ->where('category_id', $category->id)
            ->exists();

        if ($hasLeads) {
            return redirect()
                ->route('categories.index')
                ->with(
                    'error',
                    'This category has leads assigned to it. Please move or remove those leads before deleting the category.'
                );
        }

        try {
            $category->delete();

            return redirect()
                ->route('categories.index')
                ->with('success', 'Category deleted successfully.');

        } catch (\Throwable $e) {

            report($e);

            return redirect()
                ->route('categories.index')
                ->with(
                    'error',
                    'Category could not be deleted. It may be in use.'
                );
        }
    }

    /**
     * Get Logged-in User Company
     */
    private function companyId(Request $request): int
    {
        $companyId = $request->user()?->company_id;

        abort_if(
            !$companyId,
            403,
            'Company not found for current user.'
        );

        return (int) $companyId;
    }

    /**
     * Protect Company Data
     */
    private function ensureCategoryBelongsToCompany(
        Request $request,
        Category $category
    ): void {
        abort_unless(
            (int) $category->company_id
                === $this->companyId($request),
            404
        );
    }
}
