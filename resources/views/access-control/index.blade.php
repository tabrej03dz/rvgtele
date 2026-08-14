@extends('layouts.crm')

@section('title', 'Role & Permission Management')

@section('content')
<div class="space-y-6" x-data="permissionManager()" x-init="init()">

    {{-- Alerts --}}
    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            <div class="font-semibold">Please fix the following:</div>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Header like reference screenshot --}}
    <div class="rounded-2xl border border-cyan-100 bg-cyan-100/80 px-6 py-7 shadow-sm">
        <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Assign Permissions</h1>
                <p class="mt-1 text-sm text-slate-600">Assign permissions directly to users or roles.</p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <span class="rounded-full bg-white px-3 py-1.5 text-xs font-bold text-orange-600 shadow-sm">
                    Selected: <span x-text="selectedCount">0</span>
                </span>

                @canany(['access-control.user-permissions.assign', 'access-control.role-permissions.assign'])
                <button
                    type="button"
                    @click="submitAssignment()"
                    class="inline-flex items-center gap-2 rounded-lg bg-blue-500 px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-blue-600"
                >
                    <span>✓</span>
                    Assign Selected
                </button>
                @endcanany

                @can('access-control.permissions.create')
                    <button
                        type="button"
                        @click="permissionModal = true"
                        class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-emerald-700"
                    >
                        <span class="text-lg leading-none">+</span>
                        Create Permission
                    </button>
                @endcan

                @can('access-control.roles.create')
                    <button
                        type="button"
                        @click="roleModal = true"
                        class="inline-flex items-center gap-2 rounded-lg bg-violet-600 px-5 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-violet-700"
                    >
                        <span class="text-lg leading-none">+</span>
                        Create Role
                    </button>
                @endcan
            </div>
        </div>
    </div>

    {{-- Target selectors --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="grid gap-4 lg:grid-cols-3">
            <div>
                <label for="permission-user" class="mb-2 block text-sm font-semibold text-orange-600">Select User</label>
                <select
                    id="permission-user"
                    x-model="userId"
                    @change="applyTargetPermissions()"
                    class="w-full rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 shadow-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                >
                    <option value="">-- Select User --</option>
                    @foreach($users as $managedUser)
                        <option value="{{ $managedUser->id }}">
                            {{ $managedUser->name }}{{ $managedUser->email ? ' - '.$managedUser->email : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="permission-role" class="mb-2 block text-sm font-semibold text-violet-600">Select Role</label>
                <select
                    id="permission-role"
                    x-model="roleId"
                    @change="applyTargetPermissions()"
                    class="w-full rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 shadow-sm outline-none focus:border-violet-500 focus:ring-2 focus:ring-violet-100"
                >
                    <option value="">-- Select Role --</option>
                    @foreach($roles as $role)
                        @if(auth()->user()->hasRole('super_admin') || $role->name !== 'super_admin')
                            <option value="{{ $role->id }}">{{ $role->name }}</option>
                        @endif
                    @endforeach
                </select>
            </div>

            <div>
                <label for="permission-guard" class="mb-2 block text-sm font-semibold text-slate-700">Guard</label>
                <select
                    id="permission-guard"
                    x-model="guard"
                    class="w-full rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm text-slate-800 shadow-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                >
                    <option value="web">web</option>
                </select>
            </div>
        </div>

        <p class="mt-3 text-xs text-slate-500">
            User, Role, ya dono me se jo select karoge, neeche selected permissions us target par save ho jayengi.
            User ke liye yahan direct permissions manage hoti hain.
        </p>
    </div>

    {{-- Assignment form + permission table --}}
    <form
        id="permissionAssignmentForm"
        method="POST"
        action="{{ route('access-control.assign') }}"
        class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
    >
        @csrf
        @method('PUT')

        <input type="hidden" name="user_id" :value="userId">
        <input type="hidden" name="role_id" :value="roleId">
        <input type="hidden" name="guard_name" :value="guard">

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-cyan-100">
                    <tr>
                        <th class="w-20 px-6 py-4 text-left text-xs font-bold uppercase tracking-wide text-slate-700">#</th>
                        <th class="w-28 px-6 py-4 text-left text-xs font-bold uppercase tracking-wide text-slate-700">
                            <label class="inline-flex cursor-pointer items-center gap-2">
                                <input
                                    id="permission-check-all"
                                    type="checkbox"
                                    @change="toggleAll($event.target.checked)"
                                    class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                >
                                <span>All</span>
                            </label>
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wide text-slate-700">Permission Name</th>
                        <th class="w-40 px-6 py-4 text-left text-xs font-bold uppercase tracking-wide text-slate-700">Guard</th>
                        <th class="w-40 px-6 py-4 text-center text-xs font-bold uppercase tracking-wide text-slate-700">Action</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-200 bg-white">
                    @forelse($permissions as $permission)
                        <tr class="transition hover:bg-slate-50">
                            <td class="px-6 py-4 text-sm text-slate-700">{{ $loop->iteration }}</td>

                            <td class="px-6 py-4">
                                <input
                                    type="checkbox"
                                    name="permissions[]"
                                    value="{{ $permission->id }}"
                                    data-permission-checkbox
                                    @change="refreshCount()"
                                    class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                >
                            </td>

                            <td class="px-6 py-4">
                                <div class="font-medium text-slate-900">{{ $permission->name }}</div>
                            </td>

                            <td class="px-6 py-4 text-sm text-slate-600">{{ $permission->guard_name }}</td>

                            <td class="px-6 py-4 text-center">
                                @can('access-control.permissions.delete')
                                @if($permission->name !== 'access-control.view')
                                    <button
                                        type="button"
                                        @click="deletePermission({{ $permission->id }}, @js($permission->name))"
                                        class="inline-flex items-center rounded-lg bg-red-500 px-4 py-2 text-xs font-bold text-white transition hover:bg-red-600"
                                    >
                                        Delete
                                    </button>
                                @elseif($permission->name === 'access-control.view')
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-500">Protected</span>
                                @else
                                    <span class="text-xs text-slate-400">—</span>
                                @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-sm text-slate-500">
                                No permissions found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </form>

    {{-- Existing roles management --}}
    @can('access-control.roles.delete')
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-2 border-b border-slate-200 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Roles</h2>
                    <p class="mt-1 text-sm text-slate-500">Existing roles and assigned permission count.</p>
                </div>
                <span class="rounded-full bg-violet-50 px-3 py-1 text-xs font-bold text-violet-700">
                    Total: {{ $roles->count() }}
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Role</th>
                            <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Guard</th>
                            <th class="px-6 py-3 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Permissions</th>
                            <th class="px-6 py-3 text-right text-xs font-bold uppercase tracking-wide text-slate-500">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                        @foreach($roles as $role)
                            @php
                                $protectedRole = in_array($role->name, [
                                    'super_admin',
                                    'company_owner',
                                    'owner',
                                    'admin',
                                    'team_leader',
                                    'employee',
                                ], true);
                            @endphp

                            <tr>
                                <td class="px-6 py-4 text-sm font-semibold text-slate-900">{{ $role->name }}</td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ $role->guard_name }}</td>
                                <td class="px-6 py-4 text-sm text-slate-600">{{ $role->permissions->count() }}</td>
                                <td class="px-6 py-4 text-right">
                                    @if($protectedRole)
                                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-500">Protected</span>
                                    @else
                                        <button
                                            type="button"
                                            @click="deleteRole({{ $role->id }}, @js($role->name))"
                                            class="rounded-lg bg-red-500 px-4 py-2 text-xs font-bold text-white hover:bg-red-600"
                                        >
                                            Delete
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endcan

    {{-- Create Permission Modal --}}
    @if($canManageDefinitions)
        <div
            x-cloak
            x-show="permissionModal"
            x-transition.opacity
            @keydown.escape.window="permissionModal = false"
            class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/50 p-4"
        >
            <div
                @click.outside="permissionModal = false"
                class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-xl font-bold text-slate-900">Create Permission</h3>
                        <p class="mt-1 text-sm text-slate-500">Example: leads.delete, reports.export</p>
                    </div>
                    <button type="button" @click="permissionModal = false" class="text-2xl leading-none text-slate-400 hover:text-slate-700">×</button>
                </div>

                <form method="POST" action="{{ route('access-control.permissions.store') }}" class="mt-6 space-y-4">
                    @csrf
                    <input type="hidden" name="guard_name" value="web">

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Permission Name</label>
                        <input
                            type="text"
                            name="name"
                            placeholder="module.action"
                            required
                            class="w-full rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100"
                        >
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Guard</label>
                        <input
                            type="text"
                            value="web"
                            disabled
                            class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600"
                        >
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="permissionModal = false" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Cancel</button>
                        @can('access-control.permissions.create')
                        <button type="submit" class="rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-emerald-700">Create Permission</button>
                        @endcan
                    </div>
                </form>
            </div>
        </div>

        {{-- Create Role Modal --}}
        <div
            x-cloak
            x-show="roleModal"
            x-transition.opacity
            @keydown.escape.window="roleModal = false"
            class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/50 p-4"
        >
            <div
                @click.outside="roleModal = false"
                class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-xl font-bold text-slate-900">Create Role</h3>
                        <p class="mt-1 text-sm text-slate-500">Example: sales_manager, branch_manager</p>
                    </div>
                    <button type="button" @click="roleModal = false" class="text-2xl leading-none text-slate-400 hover:text-slate-700">×</button>
                </div>

                <form method="POST" action="{{ route('access-control.roles.store') }}" class="mt-6 space-y-4">
                    @csrf
                    <input type="hidden" name="guard_name" value="web">

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Role Name</label>
                        <input
                            type="text"
                            name="name"
                            placeholder="role_name"
                            required
                            class="w-full rounded-lg border border-slate-300 px-4 py-3 text-sm outline-none focus:border-violet-500 focus:ring-2 focus:ring-violet-100"
                        >
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-slate-700">Guard</label>
                        <input
                            type="text"
                            value="web"
                            disabled
                            class="w-full rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600"
                        >
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="roleModal = false" class="rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700">Cancel</button>
                        @can('access-control.roles.create')
                        <button type="submit" class="rounded-lg bg-violet-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-violet-700">Create Role</button>
                        @endcan
                    </div>
                </form>
            </div>
        </div>

        {{-- Hidden delete permission form --}}
        <form id="deletePermissionForm" method="POST" class="hidden">
            @csrf
            @method('DELETE')
        </form>

        {{-- Hidden delete role form --}}
        <form id="deleteRoleForm" method="POST" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    @endif
</div>

<script>
    function permissionManager() {
        return {
            userId: '',
            roleId: '',
            guard: @js($guard),
            selectedCount: 0,
            roleModal: false,
            permissionModal: false,

            userPermissionMap: @js($userPermissionMap),
            rolePermissionMap: @js($rolePermissionMap),

            init() {
                this.$nextTick(() => {
                    this.refreshCount();
                });
            },

            getPermissionCheckboxes() {
                return Array.from(document.querySelectorAll('[data-permission-checkbox]'));
            },

            refreshCount() {
                const boxes = this.getPermissionCheckboxes();
                const checked = boxes.filter(box => box.checked);

                this.selectedCount = checked.length;

                const all = document.getElementById('permission-check-all');
                if (all) {
                    all.checked = boxes.length > 0 && checked.length === boxes.length;
                    all.indeterminate = checked.length > 0 && checked.length < boxes.length;
                }
            },

            toggleAll(checked) {
                this.getPermissionCheckboxes().forEach(box => {
                    box.checked = checked;
                });

                this.refreshCount();
            },

            applyTargetPermissions() {
                const ids = new Set();

                if (this.userId && this.userPermissionMap[String(this.userId)]) {
                    this.userPermissionMap[String(this.userId)].forEach(id => ids.add(Number(id)));
                }

                if (this.roleId && this.rolePermissionMap[String(this.roleId)]) {
                    this.rolePermissionMap[String(this.roleId)].forEach(id => ids.add(Number(id)));
                }

                this.getPermissionCheckboxes().forEach(box => {
                    box.checked = ids.has(Number(box.value));
                });

                this.refreshCount();
            },

            submitAssignment() {
                if (!this.userId && !this.roleId) {
                    alert('Please select a User or Role first.');
                    return;
                }


                document.getElementById('permissionAssignmentForm').submit();
            },

            deletePermission(id, name) {
                if (!confirm(`Delete permission "${name}"?`)) {
                    return;
                }

                const form = document.getElementById('deletePermissionForm');
                form.action = @js(url('/access-control/permissions')) + '/' + id;
                form.submit();
            },

            deleteRole(id, name) {
                if (!confirm(`Delete role "${name}"? Users assigned to this role will lose that role.`)) {
                    return;
                }

                const form = document.getElementById('deleteRoleForm');
                form.action = @js(url('/access-control/roles')) + '/' + id;
                form.submit();
            },
        };
    }
</script>
@endsection