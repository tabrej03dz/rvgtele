<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = $this->companyId();

        $query = Team::query()
            ->with([
                'branch:id,name',
                'leader:id,name',
            ])
            ->where('company_id', $companyId);

        if ($request->filled('search')) {
            $search = trim((string) $request->search);

            $query->where(function (Builder $builder) use ($search) {
                $builder
                    ->where('name', 'like', "%{$search}%")
                    ->orWhereHas('branch', function (Builder $branchQuery) use ($search) {
                        $branchQuery->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('leader', function (Builder $leaderQuery) use ($search) {
                        $leaderQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $items = $query
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('modules.index', [
            'items' => $items,
            'title' => 'Teams',
            'routeName' => 'teams',

            /*
            |--------------------------------------------------------------------------
            | Relationship columns
            |--------------------------------------------------------------------------
            |
            | Agar modules/index.blade.php relation path support karta hai to
            | branch.name aur leader.name automatically show honge.
            |
            */

            'columns' => [
                'name',
                'branch.name',
                'leader.name',
                'is_active',
            ],

            'columnLabels' => [
                'name' => 'Team Name',
                'branch.name' => 'Branch',
                'leader.name' => 'Team Leader',
                'is_active' => 'Active',
            ],
        ]);
    }

    public function create(): View
    {
        return view('modules.form', [
            'item' => new Team(),
            'title' => 'Create Team',
            'routeName' => 'teams',
            'fields' => $this->formFields(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $data['company_id'] = $this->companyId();
        $data['is_active'] = $request->boolean('is_active');

        Team::create($data);

        return redirect()
            ->route('teams.index')
            ->with('success', 'Team created successfully.');
    }

    public function edit(Team $item): View
    {
        $this->authorizeCompanyRecord($item);

        return view('modules.form', [
            'item' => $item,
            'title' => 'Edit Team',
            'routeName' => 'teams',
            'fields' => $this->formFields(),
        ]);
    }

    public function update(Request $request, Team $item): RedirectResponse
    {
        $this->authorizeCompanyRecord($item);

        $data = $this->validated($request);
        $data['is_active'] = $request->boolean('is_active');

        $item->update($data);

        return redirect()
            ->route('teams.index')
            ->with('success', 'Team updated successfully.');
    }

    public function destroy(Team $item): RedirectResponse
    {
        $this->authorizeCompanyRecord($item);

        if ($item->users()->exists()) {
            return back()->with(
                'error',
                'This team cannot be deleted because employees are assigned to it.'
            );
        }

        $item->delete();

        return back()->with('success', 'Team deleted successfully.');
    }

    private function formFields(): array
    {
        $companyId = $this->companyId();

        $branches = Branch::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $leaders = User::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'employee_code']);

        return [
            'name' => [
                'type' => 'text',
                'label' => 'Team Name',
                'required' => true,
                'placeholder' => 'Enter team name',
            ],

            'branch_id' => [
                'type' => 'select',
                'label' => 'Branch',
                'required' => true,
                'empty_label' => 'Select Branch',
                'options' => $branches,
                'option_label' => function (Branch $branch): string {
                    return $branch->code
                        ? "{$branch->name} ({$branch->code})"
                        : $branch->name;
                },
            ],

            'leader_id' => [
                'type' => 'select',
                'label' => 'Team Leader',
                'required' => false,
                'empty_label' => 'Select Team Leader',
                'options' => $leaders,
                'option_label' => function (User $user): string {
                    return $user->employee_code
                        ? "{$user->name} ({$user->employee_code})"
                        : $user->name;
                },
            ],

            'is_active' => [
                'type' => 'checkbox',
                'label' => 'Active Team',
                'help' => 'Only active teams will be available during employee assignment.',
            ],
        ];
    }

    private function validated(Request $request): array
    {
        $companyId = $this->companyId();

        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'branch_id' => [
                'required',
                'integer',
                Rule::exists('branches', 'id')
                    ->where(fn ($query) => $query
                        ->where('company_id', $companyId)
                        ->where('is_active', true)),
            ],

            'leader_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')
                    ->where(fn ($query) => $query
                        ->where('company_id', $companyId)
                        ->where('is_active', true)),
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],
        ]);
    }

    private function companyId(): int
    {
        return (int) Auth::user()->company_id;
    }

    private function authorizeCompanyRecord(Team $team): void
    {
        abort_unless(
            (int) $team->company_id === $this->companyId(),
            403,
            'Unauthorized team access.'
        );
    }
}