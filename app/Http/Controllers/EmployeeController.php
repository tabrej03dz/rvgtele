<?php

namespace App\Http\Controllers;

use App\Models\{User, Branch, Team};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class EmployeeController extends Controller
{
    public function index(Request $r)
    {
        return view('employees.index', ['employees' => User::with(['branch', 'team', 'roles'])->where('company_id', $r->user()->company_id)->latest()->paginate(20)]);
    }
    public function create(Request $r)
    {
        return view('employees.form', ['employee' => new User, 'branches' => Branch::where('company_id', $r->user()->company_id)->get(), 'teams' => Team::where('company_id', $r->user()->company_id)->get(), 'roles' => \Spatie\Permission\Models\Role::all()]);
    }
    public function store(Request $r)
    {
        $d = $r->validate(['name' => 'required', 'email' => 'required|email|unique:users', 'phone' => 'nullable', 'employee_code' => 'nullable|unique:users', 'password' => 'required|min:8', 'branch_id' => 'nullable|exists:branches,id', 'team_id' => 'nullable|exists:teams,id', 'role' => 'required']);
        $d['company_id'] = $r->user()->company_id;
        $d['password'] = Hash::make($d['password']);
        $u = User::create($d);
        $u->assignRole($d['role']);
        return redirect()->route('employees.index')->with('success', 'Employee created.');
    }
    public function edit(Request $r, User $employee)
    {
        abort_unless($employee->company_id === $r->user()->company_id, 403);
        return view('employees.form', ['employee' => $employee, 'branches' => Branch::where('company_id', $r->user()->company_id)->get(), 'teams' => Team::where('company_id', $r->user()->company_id)->get(), 'roles' => \Spatie\Permission\Models\Role::all()]);
    }
    public function update(Request $r, User $employee)
    {
        abort_unless($employee->company_id === $r->user()->company_id, 403);
        $d = $r->validate(['name' => 'required', 'email' => 'required|email|unique:users,email,' . $employee->id, 'phone' => 'nullable', 'employee_code' => 'nullable|unique:users,employee_code,' . $employee->id, 'password' => 'nullable|min:8', 'branch_id' => 'nullable|exists:branches,id', 'team_id' => 'nullable|exists:teams,id', 'role' => 'required', 'is_active' => 'nullable|boolean']);
        if (empty($d['password'])) unset($d['password']);
        else $d['password'] = Hash::make($d['password']);
        $employee->update($d);
        $employee->syncRoles([$d['role']]);
        return redirect()->route('employees.index')->with('success', 'Employee updated.');
    }
    public function destroy(Request $r, User $employee)
    {
        abort_unless($employee->company_id === $r->user()->company_id, 403);
        abort_if($employee->id === $r->user()->id, 422, 'You cannot delete yourself.');
        $employee->delete();
        return back()->with('success', 'Employee deleted.');
    }
}
