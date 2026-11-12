<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Http\Request;

class CallingCrmController extends Controller
{
    
public function index(Request $request)
    {
        $user = $request->user();

        $isSuperAdmin = $user->hasRole('super_admin');

        // Validate filter inputs
        $filters = $request->validate([
            'search' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:255',
            'user_id' => 'nullable|integer|min:1',
        ]);

        $search = trim($filters['search'] ?? '');
        $category = $filters['category'] ?? null;
        $status = $filters['status'] ?? null;

        // User filter sirf super admin ke liye
        $selectedUserId = $isSuperAdmin
            ? ($filters['user_id'] ?? null)
            : $user->id;

        /*
        |--------------------------------------------------------------------------
        | Base Query
        |--------------------------------------------------------------------------
        | Super Admin = All assigned leads
        | Normal User = Only own assigned leads
        | Pending leads excluded
        */

        $baseQuery = Lead::query()
            ->whereNotNull('call_status')
            ->where('call_status', '!=', 'pending');

        if (!$isSuperAdmin) {
            $baseQuery->where('assigned_to', $user->id);
        }

        /*
        |--------------------------------------------------------------------------
        | Category Dropdown
        |--------------------------------------------------------------------------
        */

        $categoryQuery = clone $baseQuery;

        if ($selectedUserId !== null) {
            $categoryQuery->where(
                'assigned_to',
                $selectedUserId
            );
        }

        $categories = $categoryQuery
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        /*
        |--------------------------------------------------------------------------
        | Users Dropdown
        |--------------------------------------------------------------------------
        | Show users who have assigned non-pending leads
        */

        $users = collect();

        if ($isSuperAdmin) {

            $assignedUserIds = (clone $baseQuery)
                ->whereNotNull('assigned_to')
                ->distinct()
                ->pluck('assigned_to');

            $users = User::query()
                ->whereIn('id', $assignedUserIds)
                ->orderBy('name')
                ->get(['id', 'name']);
        }

        /*
        |--------------------------------------------------------------------------
        | Status Dropdown
        |--------------------------------------------------------------------------
        */

        $statusQuery = clone $baseQuery;

        if ($selectedUserId !== null) {
            $statusQuery->where(
                'assigned_to',
                $selectedUserId
            );
        }

        if ($category !== null && $category !== '') {
            $statusQuery->where('category', $category);
        }

        $statuses = $statusQuery
            ->distinct()
            ->orderBy('call_status')
            ->pluck('call_status');

        /*
        |--------------------------------------------------------------------------
        | Main Leads Query
        |--------------------------------------------------------------------------
        */

        $contactsQuery = clone $baseQuery;

        // Filter by assigned user
        if ($selectedUserId !== null) {
            $contactsQuery->where(
                'assigned_to',
                $selectedUserId
            );
        }

        // Category filter
        if ($category !== null && $category !== '') {
            $contactsQuery->where(
                'category',
                $category
            );
        }

        // Status filter
        if ($status !== null && $status !== '') {
            $contactsQuery->where(
                'call_status',
                $status
            );
        }

        // Search by name, phone, lead ID
        if ($search !== '') {

            $contactsQuery->where(function ($query) use ($search) {

                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");

                if (ctype_digit($search)) {
                    $query->orWhere(
                        'id',
                        (int) $search
                    );
                }
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Load Lead + History + User Information
        |--------------------------------------------------------------------------
        */

        $contacts = $contactsQuery
            ->with([
                'assignedUser:id,name',

                'callHistories' => function ($query) {
                    $query
                        ->with('user:id,name')
                        ->orderByDesc('call_at')
                        ->orderByDesc('id');
                },
            ])
            ->withCount('callHistories')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('calling-crm.index', [
            'title' => 'Calling CRM',
            'contacts' => $contacts,
            'categories' => $categories,
            'statuses' => $statuses,
            'users' => $users,
            'isSuperAdmin' => $isSuperAdmin,
            'selectedUserId' => $selectedUserId,
        ]);
    }

}
