<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class CategoryController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = $this->companyId($request);
        $search = trim((string) $request->query('search', ''));

        $categories = Category::query()
            ->where('categories.company_id', $companyId)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('categories.name', 'like', "%{$search}%")
                        ->orWhere('categories.description', 'like', "%{$search}%");
                });
            })
            ->select('categories.*')
            ->selectSub(
                DB::table('leads')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('leads.category_id', 'categories.id'),
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

    public function create(Request $request): View
    {
        return view('categories.create', [
            'title' => 'Add Category',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $companyId = $this->companyId($request);
        $validated = $this->validateCategory($request, $companyId);

        Category::create([
            'company_id' => $companyId,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()
            ->route('categories.index')
            ->with('success', 'Category created successfully.');
    }

    public function show(Request $request, Category $category): View
    {
        $this->ensureCategoryBelongsToCompany($request, $category);

        $category->setAttribute(
            'leads_count',
            DB::table('leads')->where('category_id', $category->id)->count()
        );

        return view('categories.show', [
            'title' => 'Category Details',
            'category' => $category,
        ]);
    }

    public function edit(Request $request, Category $category): View
    {
        $this->ensureCategoryBelongsToCompany($request, $category);

        return view('categories.edit', [
            'title' => 'Edit Category',
            'category' => $category,
        ]);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $this->ensureCategoryBelongsToCompany($request, $category);

        $validated = $this->validateCategory(
            $request,
            $this->companyId($request),
            $category
        );

        $category->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        return redirect()
            ->route('categories.index')
            ->with('success', 'Category updated successfully.');
    }

    public function destroy(Request $request, Category $category): RedirectResponse
    {
        $this->ensureCategoryBelongsToCompany($request, $category);
        Gate::authorize('categories.delete');

        $request->validate([
            'confirm_delete' => ['required', 'accepted'],
        ], [
            'confirm_delete.required' => 'Please confirm permanent deletion.',
            'confirm_delete.accepted' => 'Please confirm permanent deletion.',
        ]);

        $companyId = $this->companyId($request);

        try {
            $deletedLeads = DB::transaction(function () use ($category, $companyId) {
                $lockedCategory = Category::query()
                    ->where('company_id', $companyId)
                    ->whereKey($category->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                // A query builder delete permanently removes the matching rows,
                // including previously soft-deleted leads in this category.
                $count = DB::table('leads')
                    ->where('category_id', $lockedCategory->getKey())
                    ->delete();

                if ($lockedCategory->delete() !== true) {
                    throw new RuntimeException('Category deletion was cancelled.');
                }

                return $count;
            }, 3);
        } catch (QueryException | RuntimeException $exception) {
            // Keep Laravel's normal 404 response for a concurrent deletion.
            if ($exception instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                throw $exception;
            }

            report($exception);

            return redirect()
                ->route('categories.index')
                ->with('error', 'Deletion failed. No changes were saved.');
        }

        return redirect()
            ->route('categories.index')
            ->with(
                'success',
                sprintf(
                    'Category "%s" deleted. %s lead(s) permanently deleted.',
                    $category->name,
                    number_format($deletedLeads)
                )
            );
    }

    private function validateCategory(
        Request $request,
        int $companyId,
        ?Category $category = null
    ): array {
        $uniqueName = Rule::unique('categories', 'name')
            ->where('company_id', $companyId);

        if ($category !== null) {
            $uniqueName->ignore($category);
        }

        return $request->validate([
            'name' => ['required', 'string', 'max:255', $uniqueName],
            'description' => ['nullable', 'string', 'max:255'],
        ], [
            'name.required' => 'Category name is required.',
            'name.unique' => 'This category already exists.',
            'name.max' => 'Category name cannot exceed 255 characters.',
            'description.max' => 'Description cannot exceed 255 characters.',
        ]);
    }

    private function companyId(Request $request): int
    {
        $companyId = $request->user()?->company_id;

        abort_if(!$companyId, 403, 'Company not found for current user.');

        return (int) $companyId;
    }

    private function ensureCategoryBelongsToCompany(
        Request $request,
        Category $category
    ): void {
        abort_unless(
            (int) $category->company_id === $this->companyId($request),
            404
        );
    }
}