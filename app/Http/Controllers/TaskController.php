<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = $this->companyId();

        $query = Task::query()
            ->with([
                'lead:id,name,mobile',
                'assignedUser:id,name',
            ])
            ->where('company_id', $companyId);

        if ($request->filled('search')) {
            $search = trim((string) $request->search);

            $query->where(function (Builder $builder) use ($search) {
                $builder
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%")
                    ->orWhereHas('lead', function (Builder $leadQuery) use ($search) {
                        $leadQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('mobile', 'like', "%{$search}%");
                    })
                    ->orWhereHas('assignedUser', function (Builder $userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        $items = $query
            ->orderByRaw("
                CASE
                    WHEN status = 'completed' THEN 3
                    WHEN due_at < NOW() THEN 1
                    ELSE 2
                END
            ")
            ->orderBy('due_at')
            ->paginate(20)
            ->withQueryString();

        return view('modules.index', [
            'items' => $items,
            'title' => 'Tasks',
            'routeName' => 'tasks',

            'columns' => [
                'title',
                'lead.name',
                'assignedUser.name',
                'due_at',
                'priority',
                'status',
                'completed_at',
            ],

            'columnLabels' => [
                'title' => 'Task',
                'lead.name' => 'Related Lead',
                'assignedUser.name' => 'Assigned Employee',
                'due_at' => 'Due Date',
                'priority' => 'Priority',
                'status' => 'Status',
                'completed_at' => 'Completed At',
            ],
        ]);
    }

    public function create(): View
    {
        return view('modules.form', [
            'item' => new Task([
                'priority' => 'normal',
                'status' => 'pending',
            ]),
            'title' => 'Create Task',
            'routeName' => 'tasks',
            'fields' => $this->formFields(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $data['company_id'] = $this->companyId();
        $data['created_by'] = Auth::id();

        if ($data['status'] === 'completed') {
            $data['completed_at'] = now();
        } else {
            $data['completed_at'] = null;
        }

        Task::create($data);

        return redirect()
            ->route('tasks.index')
            ->with('success', 'Task created successfully.');
    }

    public function edit(Task $item): View
    {
        $this->authorizeCompanyRecord($item);

        return view('modules.form', [
            'item' => $item,
            'title' => 'Edit Task',
            'routeName' => 'tasks',
            'fields' => $this->formFields(),
        ]);
    }

    public function update(
        Request $request,
        Task $item
    ): RedirectResponse {
        $this->authorizeCompanyRecord($item);

        $data = $this->validated($request);

        if ($data['status'] === 'completed') {
            if (!$item->completed_at) {
                $data['completed_at'] = now();
            } else {
                $data['completed_at'] = $item->completed_at;
            }
        } else {
            $data['completed_at'] = null;
        }

        $item->update($data);

        return redirect()
            ->route('tasks.index')
            ->with('success', 'Task updated successfully.');
    }

    public function destroy(Task $item): RedirectResponse
    {
        $this->authorizeCompanyRecord($item);

        $item->delete();

        return back()->with('success', 'Task deleted successfully.');
    }

    private function formFields(): array
    {
        $companyId = $this->companyId();

        $leads = Lead::query()
            ->where('company_id', $companyId)
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'mobile',
                'company_name',
            ]);

        $users = User::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'employee_code',
            ]);

        return [
            'title' => [
                'type' => 'text',
                'label' => 'Task Title',
                'required' => true,
                'placeholder' => 'Example: Call customer for payment',
            ],

            'lead_id' => [
                'type' => 'select',
                'label' => 'Related Lead',
                'empty_label' => 'Select Related Lead',
                'options' => $leads,
                'option_label' => function (Lead $lead): string {
                    $details = [$lead->name];

                    if ($lead->company_name) {
                        $details[] = $lead->company_name;
                    }

                    if ($lead->mobile) {
                        $details[] = $lead->mobile;
                    }

                    return implode(' - ', $details);
                },
            ],

            'assigned_to' => [
                'type' => 'select',
                'label' => 'Assign Employee',
                'required' => true,
                'empty_label' => 'Select Employee',
                'options' => $users,
                'option_label' => function (User $user): string {
                    if ($user->employee_code) {
                        return $user->name . ' (' . $user->employee_code . ')';
                    }

                    return $user->name;
                },
            ],

            'due_at' => [
                'type' => 'datetime-local',
                'label' => 'Due Date and Time',
                'required' => true,
            ],

            'priority' => [
                'type' => 'select',
                'label' => 'Priority',
                'required' => true,
                'empty_label' => 'Select Priority',
                'options' => [
                    [
                        'id' => 'low',
                        'name' => 'Low',
                    ],
                    [
                        'id' => 'normal',
                        'name' => 'Normal',
                    ],
                    [
                        'id' => 'high',
                        'name' => 'High',
                    ],
                    [
                        'id' => 'urgent',
                        'name' => 'Urgent',
                    ],
                ],
            ],

            'status' => [
                'type' => 'select',
                'label' => 'Task Status',
                'required' => true,
                'empty_label' => 'Select Status',
                'options' => [
                    [
                        'id' => 'pending',
                        'name' => 'Pending',
                    ],
                    [
                        'id' => 'in_progress',
                        'name' => 'In Progress',
                    ],
                    [
                        'id' => 'completed',
                        'name' => 'Completed',
                    ],
                    [
                        'id' => 'cancelled',
                        'name' => 'Cancelled',
                    ],
                ],
            ],

            'description' => [
                'type' => 'textarea',
                'label' => 'Task Description',
                'placeholder' => 'Enter task instructions',
                'rows' => 4,
            ],

            'completion_notes' => [
                'type' => 'textarea',
                'label' => 'Completion Notes',
                'placeholder' => 'Enter completion details',
                'rows' => 4,
            ],
        ];
    }

    private function validated(Request $request): array
    {
        $companyId = $this->companyId();

        return $request->validate([
            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'lead_id' => [
                'nullable',
                'integer',
                Rule::exists('leads', 'id')
                    ->where(function ($query) use ($companyId) {
                        $query
                            ->where('company_id', $companyId)
                            ->whereNull('deleted_at');
                    }),
            ],

            'assigned_to' => [
                'required',
                'integer',
                Rule::exists('users', 'id')
                    ->where(function ($query) use ($companyId) {
                        $query
                            ->where('company_id', $companyId)
                            ->where('is_active', true);
                    }),
            ],

            'due_at' => [
                'required',
                'date',
            ],

            'priority' => [
                'required',
                Rule::in([
                    'low',
                    'normal',
                    'high',
                    'urgent',
                ]),
            ],

            'status' => [
                'required',
                Rule::in([
                    'pending',
                    'in_progress',
                    'completed',
                    'cancelled',
                ]),
            ],

            'completion_notes' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ]);
    }

    private function companyId(): int
    {
        return (int) Auth::user()->company_id;
    }

    private function authorizeCompanyRecord(Task $task): void
    {
        abort_unless(
            (int) $task->company_id === $this->companyId(),
            403,
            'Unauthorized task access.'
        );
    }
}