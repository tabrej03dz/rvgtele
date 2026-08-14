@extends('layouts.crm')

@section('title', 'Role & Permission Management')

@section('content')
<div class="space-y-8">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Role & Permission Management</h1>
            <p class="mt-1 text-sm text-slate-500">
                Create access rules, assign permissions to roles, and assign roles/direct permissions to users.
            </p>
        </div>
    </div>

    @if($canManageDefinitions)
        <div class="grid gap-6 xl:grid-cols-2">
            <form method="POST" action="{{ route('access-control.roles.store') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                @csrf
                <h2 class="text-lg font-semibold text-slate-900">Create New Role</h2>
                <p class="mt-1 text-sm text-slate-500">Example: branch_manager, hr_manager, sales_head</p>

                <div class="mt-5 flex flex-col gap-3 sm:flex-row">
                    <input
                        type="text"
                        name="name"
                        value="{{ old('name') }}"
                        placeholder="role_name"
                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                        required
                    >
                    <button type="submit" class="rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white hover:bg-indigo-700">
                        Create Role
                    </button>
                </div>
            </form>

            <form method="POST" action="{{ route('access-control.permissions.store') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                @csrf
                <h2 class="text-lg font-semibold text-slate-900">Create New Permission</h2>
                <p class="mt-1 text-sm text-slate-500">Example: leads.export, employees.delete, reports.download</p>

                <div class="mt-5 flex flex-col gap-3 sm:flex-row">
                    <input
                        type="text"
                        name="name"
                        placeholder="module.action"
                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100"
                        required
                    >
                    <button type="submit" class="rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800">
                        Create Permission
                    </button>
                </div>
            </form>
        </div>
    @endif

    @if($canManageDefinitions)
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-5">
                <h2 class="text-lg font-semibold text-slate-900">Permissions by Role</h2>
                <p class="mt-1 text-sm text-slate-500">Select exactly what every role is allowed to do.</p>
            </div>

            <div class="divide-y divide-slate-200">
                @foreach($roles as $role)
                    @php
                        $rolePermissionIds = $role->permissions->pluck('id')->map(fn($id) => (int) $id)->all();
                        $isSuperRole = $role->name === 'super_admin';
                    @endphp

                    <form method="POST" action="{{ route('access-control.roles.permissions.sync', $role) }}" class="p-6">
                        @csrf
                        @method('PUT')

                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="min-w-0 lg:w-56">
                                <div class="flex items-center gap-2">
                                    <h3 class="break-all text-base font-bold text-slate-900">{{ $role->name }}</h3>
                                    @if($isSuperRole)
                                        <span class="rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-bold text-amber-700">ALL ACCESS</span>
                                    @endif
                                </div>
                                <p class="mt-1 text-xs text-slate-500">{{ $role->permissions->count() }} direct role permissions</p>
                            </div>

                            <div class="grid flex-1 gap-2 sm:grid-cols-2 xl:grid-cols-3">
                                @foreach($permissions as $permission)
                                    <label class="flex items-start gap-3 rounded-xl border border-slate-200 p-3 hover:bg-slate-50">
                                        <input
                                            type="checkbox"
                                            name="permissions[]"
                                            value="{{ $permission->id }}"
                                            class="mt-0.5 h-4 w-4 rounded border-slate-300"
                                            @checked($isSuperRole || in_array((int) $permission->id, $rolePermissionIds, true))
                                            @disabled($isSuperRole)
                                        >
                                        <span class="break-all text-sm text-slate-700">{{ $permission->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="mt-5 flex justify-end">
                            <button type="submit" class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                                {{ $isSuperRole ? 'Keep All Permissions' : 'Save Role Permissions' }}
                            </button>
                        </div>
                    </form>
                @endforeach
            </div>
        </div>
    @endif

    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-5">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">User Access</h2>
                    <p class="mt-1 text-sm text-slate-500">Assign one or more roles and extra direct permissions to any allowed user.</p>
                </div>

                <form method="GET" action="{{ route('access-control.index') }}" class="flex w-full gap-2 lg:w-auto">
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search user..."
                        class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm lg:w-72"
                    >
                    <button class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white">Search</button>
                </form>
            </div>
        </div>

        <div class="divide-y divide-slate-200">
            @forelse($users as $managedUser)
                @php
                    $userRoleIds = $managedUser->roles->pluck('id')->map(fn($id) => (int) $id)->all();
                    $userPermissionIds = $managedUser->permissions->pluck('id')->map(fn($id) => (int) $id)->all();
                    $isTargetSuperAdmin = $managedUser->hasRole('super_admin');
                @endphp

                <form method="POST" action="{{ route('access-control.users.access.sync', $managedUser) }}" class="p-6">
                    @csrf
                    @method('PUT')

                    <div class="grid gap-6 2xl:grid-cols-[260px_1fr_1fr_auto]">
                        <div>
                            <div class="font-semibold text-slate-900">{{ $managedUser->name }}</div>
                            <div class="mt-1 break-all text-sm text-slate-500">{{ $managedUser->email }}</div>
                            <div class="mt-2 text-xs text-slate-400">
                                {{ $managedUser->company?->name ?? 'No Company' }}
                                @if($managedUser->employee_code)
                                    • {{ $managedUser->employee_code }}
                                @endif
                            </div>
                        </div>

                        <div>
                            <div class="mb-2 text-xs font-bold uppercase tracking-wider text-slate-500">Roles</div>
                            <div class="grid gap-2 sm:grid-cols-2">
                                @foreach($roles as $role)
                                    @if(auth()->user()->hasRole('super_admin') || $role->name !== 'super_admin')
                                        <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2">
                                            <input
                                                type="checkbox"
                                                name="roles[]"
                                                value="{{ $role->id }}"
                                                class="h-4 w-4 rounded border-slate-300"
                                                @checked(in_array((int) $role->id, $userRoleIds, true))
                                                @disabled($isTargetSuperAdmin && !auth()->user()->hasRole('super_admin'))
                                            >
                                            <span class="break-all text-sm text-slate-700">{{ $role->name }}</span>
                                        </label>
                                    @endif
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <div class="mb-2 text-xs font-bold uppercase tracking-wider text-slate-500">Direct Permissions</div>
                            <div class="grid max-h-72 gap-2 overflow-y-auto pr-1 sm:grid-cols-2">
                                @foreach($permissions as $permission)
                                    <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2">
                                        <input
                                            type="checkbox"
                                            name="permissions[]"
                                            value="{{ $permission->id }}"
                                            class="h-4 w-4 rounded border-slate-300"
                                            @checked(in_array((int) $permission->id, $userPermissionIds, true))
                                            @disabled($isTargetSuperAdmin && !auth()->user()->hasRole('super_admin'))
                                        >
                                        <span class="break-all text-sm text-slate-700">{{ $permission->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="flex items-start 2xl:justify-end">
                            <button type="submit" class="rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">
                                Save Access
                            </button>
                        </div>
                    </div>
                </form>
            @empty
                <div class="p-10 text-center text-sm text-slate-500">No users found.</div>
            @endforelse
        </div>

        @if($users->hasPages())
            <div class="border-t border-slate-200 px-6 py-4">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>
@endsection